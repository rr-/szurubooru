'use strict';

const request = require('superagent');
const config = require('./config.js');
const EventListener = require('./event_listener.js');

class Api {
    constructor() {
        this.user = null;
        this.userName = null;
        this.userPassword = null;
        this.authenticated = new EventListener();
    }

    get(url) {
        const fullUrl = this.getFullUrl(url);
        return this._process(fullUrl, () => request.get(fullUrl));
    }

    post(url, data) {
        const fullUrl = this.getFullUrl(url);
        return this._process(fullUrl, () => request.post(fullUrl).send(data));
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
                        reject(response.body);
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
            const rankIndex = config.service.userRanks.indexOf(rankName);
            if (minViableRank === null || rankIndex < minViableRank) {
                minViableRank = rankIndex;
            }
        }
        if (minViableRank === null) {
            console.error('Bad privilege name: ' + lookup);
        }
        let myRank = this.user !== null ?
            config.service.userRanks.indexOf(this.user.accessRank) :
            0;
        return myRank >= minViableRank;
    }

    login(userName, userPassword) {
        return new Promise((resolve, reject) => {
            this.userName = userName;
            this.userPassword = userPassword;
            this.get('/user/' + userName)
                .then(response => {
                    this.user = response.user;
                    resolve();
                    this.authenticated.fire();
                }).catch(response => {
                    reject(response.description);
                    this.logout();
                    this.authenticated.fire();
                });
        });
    }

    logout() {
        this.user = null;
        this.userName = null;
        this.userPassword = null;
        this.authenticated.fire();
    }

    isLoggedIn() {
        return this.userName !== null;
    }

    getFullUrl(url) {
        return (config.basic.apiUrl + '/' + url).replace(/([^:])\/+/g, '$1/');
    }
}

module.exports = new Api();
