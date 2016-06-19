'use strict';

const api = require('../api.js');

class Info {
    static get() {
        return api.get('/info')
            .then(response => {
                return Promise.resolve(response);
            }, response => {
                return Promise.reject(response.errorMessage);
            });
    }
}

module.exports = Info;
