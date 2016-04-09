'use strict';

const cookies = require('js-cookie');
const request = require('superagent');
const config = require('./config.js');
const events = require('./events.js');

class Api {
    constructor() {
        this.user = null;
        this.userName = null;
        this.userPassword = null;
    }

    get(url) {
        const fullUrl = this.getFullUrl(url);
        return this._process(fullUrl, () => request.get(fullUrl));
    }

    post(url, data) {
        const fullUrl = this.getFullUrl(url);
        return this._process(fullUrl, () => request.post(fullUrl).send(data));
    }

    put(url, data) {
        const fullUrl = this.getFullUrl(url);
        return this._process(fullUrl, () => request.put(fullUrl).send(data));
    }

    delete(url, data) {
        const fullUrl = this.getFullUrl(url);
        return this._process(fullUrl, () => request.delete(fullUrl).send(data));
    }

    _process(url, requestFactory) {
        return new Promise((resolve, reject) => {
            let req = requestFactory();
            if (this.userName && this.userPassword) {
                req.auth(this.userName, this.userPassword);
            }
            req.set('Accept', 'application/json')
                .end((error, response) => {
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
            const rankIndex = config.ranks.indexOf(rankName);
            if (minViableRank === null || rankIndex < minViableRank) {
                minViableRank = rankIndex;
            }
        }
        if (minViableRank === null) {
            console.error('Bad privilege name: ' + lookup);
        }
        let myRank = this.user !== null ?
            config.ranks.indexOf(this.user.rank) :
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
        cookies.remove('auth');
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
        cookies.remove('auth');
        this.user = null;
        this.userName = null;
        this.userPassword = null;
        events.notify(events.Authentication);
    }

    isLoggedIn() {
        return this.userName !== null;
    }

    getFullUrl(url) {
        return (config.apiUrl + '/' + url).replace(/([^:])\/+/g, '$1/');
    }
}

module.exports = new Api();
