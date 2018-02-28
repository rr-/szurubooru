'use strict';

const api = require('../api.js');
const uri = require('../util/uri.js');
const events = require('../events.js');

class UserToken extends events.EventTarget {
    constructor() {
        super();
        this._orig = {};
        this._updateFromResponse({});
    }

    get token()               { return this._token; }
    get enabled()               { return this._enabled; }
    get version()               { return this._version; }
    get creation_time()              { return this._creation_time; }

    static fromResponse(response) {
        if (typeof response.results !== 'undefined') {
            let token_list = [];
            for (let i = 0; i < response.results.length; i++) {
                const token = new UserToken();
                token._updateFromResponse(response.results[i]);
                token_list.push(token)
            }
            return token_list;
        } else {
            const ret = new UserToken();
            ret._updateFromResponse(response);
            return ret;
        }
    }

    static get(userName) {
        return api.get(uri.formatApiLink('user-tokens', userName))
            .then(response => {
                return Promise.resolve(UserToken.fromResponse(response));
            });
    }

    static create(userName) {
        return api.post(uri.formatApiLink('user-token', userName))
            .then(response => {
                return Promise.resolve(UserToken.fromResponse(response))
            });
    }

    delete(userName) {
        return api.delete(
            uri.formatApiLink('user-token', userName, this._orig._token),
            {version: this._version})
            .then(response => {
                this.dispatchEvent(new CustomEvent('delete', {
                    detail: {
                        userToken: this,
                    },
                }));
                return Promise.resolve();
            });
    }

    _updateFromResponse(response) {
        const map = {
            _token:             response.token,
            _enabled:           response.enabled,
            _version:           response.version,
            _creation_time:     response.creationTime,
        };

        Object.assign(this, map);
        Object.assign(this._orig, map);
    }
}

module.exports = UserToken;