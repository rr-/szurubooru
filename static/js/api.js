'use strict';

const request = require('superagent');
const config = require('./config.js');

class Api {
    constructor() {
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

    hasPrivilege() {
        /* TODO: implement */
        return true;
    }

    login(userName, userPassword) {
        return new Promise((resolve, reject) => {
            this.userName = userName;
            this.userPassword = userPassword;
            this.get('/user/' + userName)
                .then(() => { resolve(); })
                .catch(response => {
                    reject(response.description);
                    this.logout();
                });
        });
    }

    logout() {
        this.userName = null;
        this.userPassword = null;
    }

    isLoggedIn() {
        return this.userName !== null;
    }

    getFullUrl(url) {
        return (config.basic.apiUrl + '/' + url).replace(/([^:])\/+/g, '$1/');
    }
}

module.exports = Api;
