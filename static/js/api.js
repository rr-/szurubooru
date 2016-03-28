'use strict';

const request = require('superagent');
const config = require('./config.js');

class Api {
    get(url) {
        const fullUrl = this.getFullUrl(url);
        return this.process(fullUrl, () => request.get(fullUrl));
    }

    post(url, data) {
        const fullUrl = this.getFullUrl(url);
        return this.process(fullUrl, () => request.post(fullUrl).send(data));
    }

    process(url, requestFactory) {
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

    getFullUrl(url) {
        return (config.basic.apiUrl + '/' + url).replace(/([^:])\/+/g, '$1/');
    }
}

module.exports = Api;
