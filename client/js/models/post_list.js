'use strict';

const settings = require('../models/settings.js');
const api = require('../api.js');
const uri = require('../util/uri.js');
const AbstractList = require('./abstract_list.js');
const Post = require('./post.js');

class PostList extends AbstractList {
    static getAround(id, searchQuery) {
        return api.get(
            uri.formatApiLink(
                'post', id, 'around', {
                    query: PostList._decorateSearchQuery(searchQuery || ''),
                    fields: 'id',
                }));
    }

    static search(text, offset, limit, fields) {
        //For queries with random sorting, bypass cache by appending random number
        let cache = text != null && text.includes('sort:random')
            ? Math.round(Math.random() * 1000)
            : 0;
        return api.get(
                uri.formatApiLink(
                    'posts', {
                        query: PostList._decorateSearchQuery(text || ''),
                        offset: offset,
                        limit: limit,
                        fields: fields.join(','),
                        cache: cache,
                    }))
            .then(response => {
                return Promise.resolve(Object.assign(
                    {},
                    response,
                    {results: PostList.fromResponse(response.results)}));
            });
    }

    static _decorateSearchQuery(text) {
        const browsingSettings = settings.get();
        const disabledSafety = [];
        if (api.safetyEnabled()) {
            for (let key of Object.keys(browsingSettings.listPosts)) {
                if (browsingSettings.listPosts[key] === false) {
                    disabledSafety.push(key);
                }
            }
            if (disabledSafety.length) {
                text = `-rating:${disabledSafety.join(',')} ${text}`;
            }
        }
        return text.trim();
    }

}

PostList._itemClass = Post;
PostList._itemName = 'post';

module.exports = PostList;
