"use strict";

const api = require("../api.js");
const events = require("../events.js");

class Snapshot extends events.EventTarget {
    constructor() {
        super();
        this._orig = {};
        this._updateFromResponse({});
    }

    get operation() {
        return this._operation;
    }

    get type() {
        return this._type;
    }

    get id() {
        return this._id;
    }

    get user() {
        return this._user;
    }

    get data() {
        return this._data;
    }

    get time() {
        return this._time;
    }

    static fromResponse(response) {
        const ret = new Snapshot();
        ret._updateFromResponse(response);
        return ret;
    }

    _updateFromResponse(response) {
        const map = {
            _operation: response.operation,
            _type: response.type,
            _id: response.id,
            _user: response.user,
            _data: response.data,
            _time: response.time,
        };

        Object.assign(this, map);
    }
}

module.exports = Snapshot;
