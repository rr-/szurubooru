'use strict';

const api = require('../api.js');
const AbstractList = require('./abstract_list.js');
const Post = require('./post.js');

class PostList extends AbstractList {
    static getAround(id, searchQuery) {
        return api.get(`/post/${id}/around?fields=id&query=${searchQuery}`)
            .then(response => {
                return Promise.resolve(response);
            }).catch(response => {
                return Promise.reject(response.description);
            });
    }

    static search(text, page, pageSize, fields) {
        const url =
            `/posts/?query=${text}` +
            `&page=${page}` +
            `&pageSize=${pageSize}` +
            `&fields=${fields.join(',')}`;
        return api.get(url).then(response => {
            return Promise.resolve(Object.assign(
                {},
                response,
                {results: PostList.fromResponse(response.results)}));
        });
    }
}

PostList._itemClass = Post;
PostList._itemName = 'post';

module.exports = PostList;
