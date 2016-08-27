'use strict';

const api = require('../api.js');
const Post = require('./post.js');

class Info {
    static get() {
        return api.get('/info')
            .then(response => {
                return Promise.resolve(Object.assign(
                    {},
                    response,
                    {
                        featuredPost: response.featuredPost ?
                            Post.fromResponse(response.featuredPost) :
                            undefined
                    }));
            }, response => {
                return Promise.reject(response.errorMessage);
            });
    }
}

module.exports = Info;
