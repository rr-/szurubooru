'use strict';

const api = require('../api.js');
const uri = require('../util/uri.js');
const AbstractList = require('./abstract_list.js');
const Post = require('./post.js');

class PostList extends AbstractList {
    static getAround(id, searchQuery) {
        return api.get(
            uri.formatApiLink(
                'post', id, 'around', {query: searchQuery, fields: 'id'}));
    }

    static search(text, offset, limit, fields) {
        return api.get(
                uri.formatApiLink(
                    'posts', {
                        query: text,
                        offset: offset,
                        limit: limit,
                        fields: fields.join(','),
                    }))
            .then(response => {
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
