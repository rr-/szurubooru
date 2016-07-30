'use strict';

const api = require('../api.js');
const Post = require('./post.js');

class Info {
    static get() {
        return api.get('/info')
            .then(response => {
                if (response.featuredPost) {
                    response.featuredPost =
                        Post.fromResponse(response.featuredPost);
                }
                return Promise.resolve(response);
            }, response => {
                return Promise.reject(response.errorMessage);
            });
    }
}

module.exports = Info;
