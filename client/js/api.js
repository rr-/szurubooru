'use strict';

const nprogress = require('nprogress');
const cookies = require('js-cookie');
const request = require('superagent');
const config = require('./config.js');
const events = require('./events.js');

class Api extends events.EventTarget {
    constructor() {
        super();
        this.user = null;
        this.userName = null;
        this.userPassword = null;
        this.cache = {};
        this.allRanks = [
            'anonymous',
            'restricted',
            'regular',
            'power',
            'moderator',
            'administrator',
            'nobody',
        ];
        this.rankNames = new Map([
            ['anonymous', 'Anonymous'],
            ['restricted', 'Restricted user'],
            ['regular', 'Regular user'],
            ['power', 'Power user'],
            ['moderator', 'Moderator'],
            ['administrator', 'Administrator'],
            ['nobody', 'Nobody'],
        ]);
    }

    get(url, options) {
        if (url in this.cache) {
            return new Promise((resolve, reject) => {
                resolve(this.cache[url]);
            });
        }
        return this._process(url, request.get, {}, {}, options)
            .then(response => {
                this.cache[url] = response;
                return Promise.resolve(response);
            });
    }

    post(url, data, files, options) {
        this.cache = {};
        return this._process(url, request.post, data, files, options);
    }

    put(url, data, files, options) {
        this.cache = {};
        return this._process(url, request.put, data, files, options);
    }

    delete(url, options) {
        this.cache = {};
        return this._process(url, request.delete, {}, {}, options);
    }

    _process(url, requestFactory, data, files, options) {
        options = options || {};
        const fullUrl = this._getFullUrl(url);
        return new Promise((resolve, reject) => {
            if (!options.noProgress) {
                nprogress.start();
            }
            let req = requestFactory(fullUrl);
            if (data) {
                req.attach('metadata', new Blob([JSON.stringify(data)]));
            }
            if (files) {
                for (let key of Object.keys(files)) {
                    req.attach(key, files[key] || new Blob());
                }
            }
            if (this.userName && this.userPassword) {
                req.auth(this.userName, this.userPassword);
            }
            req.set('Accept', 'application/json')
                .end((error, response) => {
                    nprogress.done();
                    if (error) {
                        reject(response && response.body ? response.body : {
                            'title': 'Networking error',
                            'description': error.message});
                    } else {
                        resolve(response.body);
                    }
                });
        });
    }

    hasPrivilege(lookup) {
        let minViableRank = null;
        for (let privilege of Object.keys(config.privileges)) {
            if (!privilege.startsWith(lookup)) {
                continue;
            }
            const rankName = config.privileges[privilege];
            const rankIndex = this.allRanks.indexOf(rankName);
            if (minViableRank === null || rankIndex < minViableRank) {
                minViableRank = rankIndex;
            }
        }
        if (minViableRank === null) {
            throw `Bad privilege name: ${lookup}`;
        }
        let myRank = this.user !== null ?
            this.allRanks.indexOf(this.user.rank) :
            0;
        return myRank >= minViableRank;
    }

    loginFromCookies() {
        return new Promise((resolve, reject) => {
            const auth = cookies.getJSON('auth');
            if (auth && auth.user && auth.password) {
                this.login(auth.user, auth.password, true)
                    .then(resolve)
                    .catch(errorMessage => {
                        reject(errorMessage);
                    });
            } else {
                resolve();
            }
        });
    }

    login(userName, userPassword, doRemember) {
        this.cache = {};
        return new Promise((resolve, reject) => {
            this.userName = userName;
            this.userPassword = userPassword;
            this.get('/user/' + userName + '?bump-login=true')
                .then(response => {
                    const options = {};
                    if (doRemember) {
                        options.expires = 365;
                    }
                    cookies.set(
                        'auth',
                        {'user': userName, 'password': userPassword},
                        options);
                    this.user = response;
                    resolve();
                    this.dispatchEvent(new CustomEvent('login'));
                }, response => {
                    reject(response.description);
                    this.logout();
                });
        });
    }

    logout() {
        this.user = null;
        this.userName = null;
        this.userPassword = null;
        this.dispatchEvent(new CustomEvent('logout'));
    }

    forget() {
        cookies.remove('auth');
    }

    isLoggedIn(user) {
        if (user) {
            return this.userName !== null &&
                this.userName.toLowerCase() === user.name.toLowerCase();
        } else {
            return this.userName !== null;
        }
    }

    _getFullUrl(url) {
        return (config.apiUrl + '/' + encodeURI(url))
            .replace(/([^:])\/+/g, '$1/');
    }
}

module.exports = new Api();
