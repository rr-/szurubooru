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

    get token()          { return this._token; }
    get note()           { return this._note; }
    get enabled()        { return this._enabled; }
    get version()        { return this._version; }
    get expirationTime() { return this._expirationTime; }
    get creationTime()   { return this._creationTime; }

    static fromResponse(response) {
        if (typeof response.results !== 'undefined') {
            let tokenList = [];
            for (let responseToken of response.results) {
                const token = new UserToken();
                token._updateFromResponse(responseToken);
                tokenList.push(token)
            }
            return tokenList;
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

    static create(userName, note, expirationTime) {
        let userTokenRequest = {
            enabled: true
        };
        if (note) {
            userTokenRequest.note = note;
        }
        if (expirationTime) {
            userTokenRequest.expirationTime = expirationTime;
        }
        return api.post(uri.formatApiLink('user-token', userName), userTokenRequest)
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
            _token:          response.token,
            _note:           response.note,
            _enabled:        response.enabled,
            _expirationTime: response.expirationTime,
            _version:        response.version,
            _creationTime:   response.creationTime,
        };

        Object.assign(this, map);
        Object.assign(this._orig, map);
    }
}

module.exports = UserToken;
