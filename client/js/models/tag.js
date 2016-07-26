'use strict';

const api = require('../api.js');
const events = require('../events.js');
const misc = require('../util/misc.js');

class Tag extends events.EventTarget {
    constructor() {
        super();
        this._orig = {};
        this._updateFromResponse({});
    }

    get names()             { return this._names; }
    get category()          { return this._category; }
    get description()       { return this._description; }
    get suggestions()       { return this._suggestions; }
    get implications()      { return this._implications; }
    get postCount()         { return this._postCount; }
    get creationTime()      { return this._creationTime; }
    get lastEditTime()      { return this._lastEditTime; }

    set names(value)        { this._names = value; }
    set category(value)     { this._category = value; }
    set description(value)  { this._description = value; }
    set implications(value) { this._implications = value; }
    set suggestions(value)  { this._suggestions = value; }

    static fromResponse(response) {
        const ret = new Tag();
        ret._updateFromResponse(response);
        return ret;
    }

    static get(id) {
        return api.get('/tag/' + id)
            .then(response => {
                return Promise.resolve(Tag.fromResponse(response));
            }, response => {
                return Promise.reject(response.description);
            });
    }

    save() {
        const detail = {};

        // send only changed fields to avoid user privilege violation
        if (misc.arraysDiffer(this._names, this._orig._names)) {
            detail.names = this._names;
        }
        if (this._category !== this._orig._category) {
            detail.category = this._category;
        }
        if (this._description !== this._orig._description) {
            detail.description = this._description;
        }
        if (misc.arraysDiffer(this._implications, this._orig._implications)) {
            detail.implications = this._implications;
        }
        if (misc.arraysDiffer(this._suggestions, this._orig._suggestions)) {
            detail.suggestions = this._suggestions;
        }

        let promise = this._origName ?
            api.put('/tag/' + this._origName, detail) :
            api.post('/tags', detail);
        return promise
            .then(response => {
                this._updateFromResponse(response);
                this.dispatchEvent(new CustomEvent('change', {
                    detail: {
                        tag: this,
                    },
                }));
                return Promise.resolve();
            }, response => {
                return Promise.reject(response.description);
            });
    }

    merge(targetName) {
        return api.post('/tag-merge/', {
                remove: this._origName,
                mergeTo: targetName,
            }).then(response => {
                this._updateFromResponse(response);
                this.dispatchEvent(new CustomEvent('change', {
                    detail: {
                        tag: this,
                    },
                }));
                return Promise.resolve();
            }, response => {
                return Promise.reject(response.description);
            });
    }

    delete() {
        return api.delete('/tag/' + this._origName)
            .then(response => {
                this.dispatchEvent(new CustomEvent('delete', {
                    detail: {
                        tag: this,
                    },
                }));
                return Promise.resolve();
            }, response => {
                return Promise.reject(response.description);
            });
    }

    _updateFromResponse(response) {
        const map = {
            _origName:     response.names ? response.names[0] : null,
            _names:        response.names,
            _category:     response.category,
            _description:  response.description,
            _implications: response.implications,
            _suggestions:  response.suggestions,
            _creationTime: response.creationTime,
            _lastEditTime: response.lastEditTime,
            _postCount:    response.usages,
        };

        Object.assign(this, map);
        Object.assign(this._orig, map);
    }
};

module.exports = Tag;
