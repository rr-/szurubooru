"use strict";

const api = require("../api.js");
const uri = require("../util/uri.js");
const events = require("../events.js");

class BannedPost extends events.EventTarget {
    constructor() {
        super();
        this._checksum = "";
        this._time = new Date();
    }

    get checksum() {
        return this._checksum;
    }

    get time() {
        return this._time;
    }

    set checksum(value) {
        this._checksum = value;
    }

    set time(value) {
        this._time = value;
    }

    static fromResponse(response) {
        const ret = new BannedPost();
        ret._updateFromResponse(response);
        return ret;
    }

    delete() {
        return api
            .delete(uri.formatApiLink("post-ban", this._checksum))
            .then((response) => {
                this.dispatchEvent(
                    new CustomEvent("delete", {
                        detail: {
                            bannedPost: this,
                        },
                    })
                );
                return Promise.resolve();
            });
    }

    _updateFromResponse(response) {
        this._checksum = response.checksum;
        this.time = response.time;
    }
}

module.exports = BannedPost;
