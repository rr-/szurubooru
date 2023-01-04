"use strict";

const settings = require("../models/settings.js");
const api = require("../api.js");
const uri = require("../util/uri.js");
const AbstractList = require("./abstract_list.js");
const Post = require("./post.js");

class PostList extends AbstractList {
    static getAround(id, searchQuery) {
        return api.get(
            uri.formatApiLink("post", id, "around", {
                query: PostList._decorateSearchQuery(searchQuery || ""),
                fields: "id",
            })
        );
    }

    static getNearbyPoolPosts(id) {
        return api.get(
            uri.formatApiLink("post", id, "pools-nearby", {
                fields: "id",
            })
        );
    }

    static search(text, offset, limit, fields) {
        return api
            .get(
                uri.formatApiLink("posts", {
                    query: PostList._decorateSearchQuery(text || ""),
                    offset: offset,
                    limit: limit,
                    fields: fields.join(","),
                })
            )
            .then((response) => {
                return Promise.resolve(
                    Object.assign({}, response, {
                        results: PostList.fromResponse(response.results),
                    })
                );
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
                text = `-rating:${disabledSafety.join(",")} ${text}`;
            }
        }
        return text.trim();
    }

    hasPostId(testId) {
        for (let post of this._list) {
            if (post.id === testId) {
                return true;
            }
        }
        return false;
    }

    addById(id) {
        if (this.hasPostId(id)) {
            return;
        }

        let post = Post.fromResponse({ id: id });
        this.add(post);
    }

    removeById(testId) {
        for (let post of this._list) {
            if (post.id === testId) {
                this.remove(post);
            }
        }
    }
}

PostList._itemClass = Post;
PostList._itemName = "post";

module.exports = PostList;
