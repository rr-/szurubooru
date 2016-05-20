'use strict';

const nprogress = require('nprogress');
const cookies = require('js-cookie');
const request = require('superagent');
const config = require('./config.js');
const events = require('./events.js');

class Api {
    constructor() {
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
    }

    get(url) {
        if (url in this.cache) {
            return new Promise((resolve, reject) => {
                resolve(this.cache[url]);
            });
        }
        return this._process(url, request.get).then(response => {
            this.cache[url] = response;
            return Promise.resolve(response);
        });
    }

    post(url, data, files) {
        this.cache = {};
        return this._process(url, request.post, data, files);
    }

    put(url, data, files) {
        this.cache = {};
        return this._process(url, request.put, data, files);
    }

    delete(url) {
        this.cache = {};
        return this._process(url, request.delete);
    }

    _process(url, requestFactory, data, files) {
        const fullUrl = this._getFullUrl(url);
        return new Promise((resolve, reject) => {
            nprogress.start();
            let req = requestFactory(fullUrl);
            if (data) {
                req.attach('metadata', new Blob([JSON.stringify(data)]));
            }
            if (files) {
                for (let key of Object.keys(files)) {
                    req.attach(key, files[key]);
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
            console.error('Bad privilege name: ' + lookup);
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
                    this.user = response.user;
                    resolve();
                    events.notify(events.Authentication);
                }).catch(response => {
                    reject(response.description);
                    this.logout();
                    events.notify(events.Authentication);
                });
        });
    }

    logout() {
        this.user = null;
        this.userName = null;
        this.userPassword = null;
        events.notify(events.Authentication);
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
        return (config.apiUrl + '/' + url).replace(/([^:])\/+/g, '$1/');
    }
}

module.exports = new Api();
