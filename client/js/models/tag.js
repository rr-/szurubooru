'use strict';

const api = require('../api.js');
const events = require('../events.js');

class Tag extends events.EventTarget {
    constructor() {
        super();
        this._origName     = null;
        this._names        = null;
        this._category     = null;
        this._suggestions  = null;
        this._implications = null;
        this._postCount    = null;
        this._creationTime = null;
        this._lastEditTime = null;
    }

    get names()             { return this._names; }
    get category()          { return this._category; }
    get suggestions()       { return this._suggestions; }
    get implications()      { return this._implications; }
    get postCount()         { return this._postCount; }
    get creationTime()      { return this._creationTime; }
    get lastEditTime()      { return this._lastEditTime; }

    set names(value)        { this._names = value; }
    set category(value)     { this._category = value; }
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
        const detail = {
            names: this.names,
            category: this.category,
            implications: this.implications,
            suggestions: this.suggestions,
        };
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
        this._origName     = response.names ? response.names[0] : null;
        this._names        = response.names;
        this._category     = response.category;
        this._implications = response.implications;
        this._suggestions  = response.suggestions;
        this._creationTime = response.creationTime;
        this._lastEditTime = response.lastEditTime;
        this._postCount    = response.usages;
    }
};

module.exports = Tag;
