'use strict';

const api = require('../api.js');
const events = require('../events.js');

class TagCategory extends events.EventTarget {
    constructor() {
        super();
        this._name      = '';
        this._color     = '#000000';
        this._tagCount  = 0;
        this._isDefault = false;
        this._origName  = null;
        this._origColor = null;
    }

    get name()        { return this._name; }
    get color()       { return this._color; }
    get tagCount()    { return this._tagCount; }
    get isDefault()   { return this._isDefault; }
    get isTransient() { return !this._origName; }

    set name(value)   { this._name = value; }
    set color(value)  { this._color = value; }

    static fromResponse(response) {
        const ret = new TagCategory();
        ret._updateFromResponse(response);
        return ret;
    }

    save() {
        const data = {};
        if (this.name !== this._origName) {
            data.name = this.name;
        }
        if (this.color !== this._origColor) {
            data.color = this.color;
        }

        if (!Object.keys(data).length) {
            return Promise.resolve();
        }

        let promise = this._origName ?
            api.put('/tag-category/' + this._origName, data) :
            api.post('/tag-categories', data);

        return promise
            .then(response => {
                this._updateFromResponse(response);
                this.dispatchEvent(new CustomEvent('change', {
                    detail: {
                        tagCategory: this,
                    },
                }));
                return Promise.resolve();
            }, response => {
                return Promise.reject(response.description);
            });
    }

    delete() {
        return api.delete('/tag-category/' + this._origName)
            .then(response => {
                this.dispatchEvent(new CustomEvent('delete', {
                    detail: {
                        tagCategory: this,
                    },
                }));
                return Promise.resolve();
            }, response => {
                return Promise.reject(response.description);
            });
    }

    _updateFromResponse(response) {
        this._name      = response.name;
        this._color     = response.color;
        this._isDefault = response.default;
        this._tagCount  = response.usages;
        this._origName  = this.name;
        this._origColor = this.color;
    }
}

module.exports = TagCategory;
