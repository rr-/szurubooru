"use strict";

const api = require("../api.js");
const uri = require("../util/uri.js");
const events = require("../events.js");
const misc = require("../util/misc.js");

class Tag extends events.EventTarget {
    constructor() {
        const TagList = require("./tag_list.js");

        super();
        this._orig = {};

        for (let obj of [this, this._orig]) {
            obj._suggestions = new TagList();
            obj._implications = new TagList();
        }

        this._updateFromResponse({});
    }

    get names() {
        return this._names;
    }

    get category() {
        return this._category;
    }

    get description() {
        return this._description;
    }

    get suggestions() {
        return this._suggestions;
    }

    get implications() {
        return this._implications;
    }

    get postCount() {
        return this._postCount;
    }

    get creationTime() {
        return this._creationTime;
    }

    get lastEditTime() {
        return this._lastEditTime;
    }

    set names(value) {
        this._names = value;
    }

    set category(value) {
        this._category = value;
    }

    set description(value) {
        this._description = value;
    }

    static fromResponse(response) {
        const ret = new Tag();
        ret._updateFromResponse(response);
        return ret;
    }

    static get(name) {
        return api.get(uri.formatApiLink("tag", name)).then((response) => {
            return Promise.resolve(Tag.fromResponse(response));
        });
    }

    save() {
        const detail = { version: this._version };

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
        if (misc.arraysDiffer(this._implications, this._orig._implications)) {
            detail.implications = this._implications.map(
                (relation) => relation.names[0]
            );
        }
        if (misc.arraysDiffer(this._suggestions, this._orig._suggestions)) {
            detail.suggestions = this._suggestions.map(
                (relation) => relation.names[0]
            );
        }

        let promise = this._origName
            ? api.put(uri.formatApiLink("tag", this._origName), detail)
            : api.post(uri.formatApiLink("tags"), detail);
        return promise.then((response) => {
            this._updateFromResponse(response);
            this.dispatchEvent(
                new CustomEvent("change", {
                    detail: {
                        tag: this,
                    },
                })
            );
            return Promise.resolve();
        });
    }

    merge(targetName, addAlias) {
        return api
            .get(uri.formatApiLink("tag", targetName))
            .then((response) => {
                return api.post(uri.formatApiLink("tag-merge"), {
                    removeVersion: this._version,
                    remove: this._origName,
                    mergeToVersion: response.version,
                    mergeTo: targetName,
                });
            })
            .then((response) => {
                if (!addAlias) {
                    return Promise.resolve(response);
                }
                return api.put(uri.formatApiLink("tag", targetName), {
                    version: response.version,
                    names: response.names.concat(this._names),
                });
            })
            .then((response) => {
                this._updateFromResponse(response);
                this.dispatchEvent(
                    new CustomEvent("change", {
                        detail: {
                            tag: this,
                        },
                    })
                );
                return Promise.resolve();
            });
    }

    delete() {
        return api
            .delete(uri.formatApiLink("tag", this._origName), {
                version: this._version,
            })
            .then((response) => {
                this.dispatchEvent(
                    new CustomEvent("delete", {
                        detail: {
                            tag: this,
                        },
                    })
                );
                return Promise.resolve();
            });
    }

    _updateFromResponse(response) {
        const map = {
            _version: response.version,
            _origName: response.names ? response.names[0] : null,
            _names: response.names,
            _category: response.category,
            _description: response.description,
            _creationTime: response.creationTime,
            _lastEditTime: response.lastEditTime,
            _postCount: response.usages || 0,
        };

        for (let obj of [this, this._orig]) {
            obj._suggestions.sync(response.suggestions);
            obj._implications.sync(response.implications);
        }

        Object.assign(this, map);
        Object.assign(this._orig, map);
    }
}

module.exports = Tag;
