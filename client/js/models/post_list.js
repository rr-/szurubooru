'use strict';

const events = require('../events.js');
const Post = require('./post.js');

class PostList extends events.EventTarget {
    constructor(posts) {
        super();
        this._list = [];
    }

    static fromResponse(postsResponse) {
        const postList = new PostList();
        for (let postResponse of postsResponse) {
            postList._list.push(Post.fromResponse(postResponse));
        }
        return postList;
    }

    get posts() {
        return [...this._list];
    }

    get length() {
        return this._list.length;
    }

    [Symbol.iterator]() {
        return this._list[Symbol.iterator]();
    }
}

module.exports = PostList;
