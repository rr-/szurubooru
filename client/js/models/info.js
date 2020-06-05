"use strict";

const api = require("../api.js");
const uri = require("../util/uri.js");
const Post = require("./post.js");

class Info {
    static get() {
        return api.get(uri.formatApiLink("info")).then((response) => {
            return Promise.resolve(
                Object.assign({}, response, {
                    featuredPost: response.featuredPost
                        ? Post.fromResponse(response.featuredPost)
                        : undefined,
                })
            );
        });
    }
}

module.exports = Info;
