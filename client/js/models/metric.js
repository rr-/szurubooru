'use strict';

const api = require('../api.js');
const uri = require('../util/uri.js');
const events = require('../events.js');
const misc = require('../util/misc.js');
const Tag = require('./tag.js');

class Metric extends events.EventTarget {
    constructor() {
        super();
        this._orig = {};

        this._updateFromResponse({});
    }

    get version()  { return this._version; }
    get min()      { return this._min; }
    get max()      { return this._max; }
    get tag()      { return this._tag; }

    set min(value) { this._min = value; }
    set max(value) { this._max = value; }

    static fromResponse(response) {
        const ret = new Metric();
        ret._updateFromResponse(response);
        return ret;
    }

    static get(name) {
        //TODO get metric. Or only via tag?
        return api.get(uri.formatApiLink('metric', name))
            .then(response => {
                return Promise.resolve(Metric.fromResponse(response));
            });
    }

    save() {
        const detail = {version: this._version};

        if (this._min !== this._orig._min) {
            detail.min = this._min;
        }
        if (this._max !== this._orig._max) {
            detail.max = this._max;
        }

        return api.post(uri.formatApiLink('metrics'), detail)
            .then(response => {
                this._updateFromResponse(response);
                this.dispatchEvent(new CustomEvent('change', {
                    detail: {
                        metric: this,
                    },
                }));
                return Promise.resolve();
            });
    }

    delete() {
        return api.delete(
            uri.formatApiLink('metric', this._orig),
            {version: this._version})
            .then(response => {
                this.dispatchEvent(new CustomEvent('delete', {
                    detail: {
                        metric: this,
                    },
                }));
                return Promise.resolve();
            });
    }

    _updateFromResponse(response) {
        const map = {
            _version:      response.version,
            _min:          response.min,
            _max:          response.max,
            _tag:          Tag.fromResponse(response.tag || {}),
        };

        Object.assign(this, map);
        Object.assign(this._orig, map);
    }
}

module.exports = Metric;
