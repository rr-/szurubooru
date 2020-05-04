'use strict';

const api = require('../api.js');
const uri = require('../util/uri.js');
const events = require('../events.js');
const misc = require('../util/misc.js');

class Pool extends events.EventTarget {
    constructor() {
        // const PoolList = require('./pool_list.js');

        super();
        this._orig = {};

        for (let obj of [this, this._orig]) {
            // TODO
            // obj._suggestions = new PoolList();
            // obj._implications = new PoolList();
        }

        this._updateFromResponse({});
    }

    get id()                { return this._id; }
    get names()             { return this._names; }
    get category()          { return this._category; }
    get description()       { return this._description; }
  /* get suggestions()       { return this._suggestions; }
   * get implications()      { return this._implications; } */
    get postCount()         { return this._postCount; }
    get creationTime()      { return this._creationTime; }
    get lastEditTime()      { return this._lastEditTime; }

    set names(value)        { this._names = value; }
    set category(value)     { this._category = value; }
    set description(value)  { this._description = value; }

    static fromResponse(response) {
        const ret = new Pool();
        ret._updateFromResponse(response);
        return ret;
    }

    static get(id) {
        return api.get(uri.formatApiLink('pool', id))
            .then(response => {
                return Promise.resolve(Pool.fromResponse(response));
            });
    }

    save() {
        const detail = {version: this._version};

        // send only changed fields to avoid user privilege violation
        if (misc.arraysDiffer(this._names, this._orig._names, true)) {
            detail.names = this._names;
        }
        if (this._category !== this._orig._category) {
            detail.category = this._category;
        }
        if (this._description !== this._orig._description) {
            detail.description = this._description;
        }
        // TODO
        // if (misc.arraysDiffer(this._implications, this._orig._implications)) {
        //     detail.implications = this._implications.map(
        //         relation => relation.names[0]);
        // }
        // if (misc.arraysDiffer(this._suggestions, this._orig._suggestions)) {
        //     detail.suggestions = this._suggestions.map(
        //         relation => relation.names[0]);
        // }

        let promise = this._id ?
            api.put(uri.formatApiLink('pool', this._id), detail) :
            api.post(uri.formatApiLink('pools'), detail);
        return promise
            .then(response => {
                this._updateFromResponse(response);
                this.dispatchEvent(new CustomEvent('change', {
                    detail: {
                        pool: this,
                    },
                }));
                return Promise.resolve();
            });
    }

    merge(targetId, addAlias) {
        return api.get(uri.formatApiLink('pool', targetId))
            .then(response => {
                return api.post(uri.formatApiLink('pool-merge'), {
                    removeVersion: this._version,
                    remove: this._id,
                    mergeToVersion: response.version,
                    mergeTo: targetId,
                });
            }).then(response => {
                if (!addAlias) {
                    return Promise.resolve(response);
                }
                return api.put(uri.formatApiLink('pool', targetId), {
                    version: response.version,
                    names: response.names.concat(this._names),
                });
            }).then(response => {
                this._updateFromResponse(response);
                this.dispatchEvent(new CustomEvent('change', {
                    detail: {
                        pool: this,
                    },
                }));
                return Promise.resolve();
            });
    }

    delete() {
        return api.delete(
                uri.formatApiLink('pool', this._id),
                {version: this._version})
            .then(response => {
                this.dispatchEvent(new CustomEvent('delete', {
                    detail: {
                        pool: this,
                    },
                }));
                return Promise.resolve();
            });
    }

    _updateFromResponse(response) {
        const map = {
            _id:           response.id,
            _version:      response.version,
            _origName:     response.names ? response.names[0] : null,
            _names:        response.names,
            _category:     response.category,
            _description:  response.description,
            _creationTime: response.creationTime,
            _lastEditTime: response.lastEditTime,
            _postCount:    response.usages || 0,
        };

        for (let obj of [this, this._orig]) {
          // TODO
          // obj._suggestions.sync(response.suggestions);
          // obj._implications.sync(response.implications);
        }

        Object.assign(this, map);
        Object.assign(this._orig, map);
    }
};

module.exports = Pool;
