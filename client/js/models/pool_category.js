"use strict";

const api = require("../api.js");
const uri = require("../util/uri.js");
const events = require("../events.js");

class PoolCategory extends events.EventTarget {
    constructor() {
        super();
        this._name = "";
        this._color = "#000000";
        this._poolCount = 0;
        this._isDefault = false;
        this._origName = null;
        this._origColor = null;
    }

    get name() {
        return this._name;
    }

    get color() {
        return this._color;
    }

    get poolCount() {
        return this._poolCount;
    }

    get isDefault() {
        return this._isDefault;
    }

    get isTransient() {
        return !this._origName;
    }

    set name(value) {
        this._name = value;
    }

    set color(value) {
        this._color = value;
    }

    static fromResponse(response) {
        const ret = new PoolCategory();
        ret._updateFromResponse(response);
        return ret;
    }

    save() {
        const detail = { version: this._version };

        if (this.name !== this._origName) {
            detail.name = this.name;
        }
        if (this.color !== this._origColor) {
            detail.color = this.color;
        }

        if (!Object.keys(detail).length) {
            return Promise.resolve();
        }

        let promise = this._origName
            ? api.put(
                  uri.formatApiLink("pool-category", this._origName),
                  detail
              )
            : api.post(uri.formatApiLink("pool-categories"), detail);

        return promise.then((response) => {
            this._updateFromResponse(response);
            this.dispatchEvent(
                new CustomEvent("change", {
                    detail: {
                        poolCategory: this,
                    },
                })
            );
            return Promise.resolve();
        });
    }

    delete() {
        return api
            .delete(uri.formatApiLink("pool-category", this._origName), {
                version: this._version,
            })
            .then((response) => {
                this.dispatchEvent(
                    new CustomEvent("delete", {
                        detail: {
                            poolCategory: this,
                        },
                    })
                );
                return Promise.resolve();
            });
    }

    _updateFromResponse(response) {
        this._version = response.version;
        this._name = response.name;
        this._color = response.color;
        this._isDefault = response.default;
        this._poolCount = response.usages;
        this._origName = this.name;
        this._origColor = this.color;
    }
}

module.exports = PoolCategory;
