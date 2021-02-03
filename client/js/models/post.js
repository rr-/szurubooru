"use strict";

const api = require("../api.js");
const uri = require("../util/uri.js");
const tags = require("../tags.js");
const events = require("../events.js");
const TagList = require("./tag_list.js");
const NoteList = require("./note_list.js");
const CommentList = require("./comment_list.js");
const PoolList = require("./pool_list.js");
const Pool = require("./pool.js");
const misc = require("../util/misc.js");

class Post extends events.EventTarget {
    constructor() {
        super();
        this._orig = {};

        for (let obj of [this, this._orig]) {
            obj._tags = new TagList();
            obj._notes = new NoteList();
            obj._comments = new CommentList();
            obj._pools = new PoolList();
        }

        this._updateFromResponse({});
    }

    get id() {
        return this._id;
    }

    get type() {
        return this._type;
    }

    get mimeType() {
        return this._mimeType;
    }

    get checksumSHA1() {
        return this._checksumSHA1;
    }

    get checksumMD5() {
        return this._checksumMD5;
    }

    get creationTime() {
        return this._creationTime;
    }

    get user() {
        return this._user;
    }

    get safety() {
        return this._safety;
    }

    get contentUrl() {
        return this._contentUrl;
    }

    get fullContentUrl() {
        return this._fullContentUrl;
    }

    get thumbnailUrl() {
        return this._thumbnailUrl;
    }

    get source() {
        return this._source;
    }

    get sourceSplit() {
        return this._source.split("\n");
    }

    get canvasWidth() {
        return this._canvasWidth || 800;
    }

    get canvasHeight() {
        return this._canvasHeight || 450;
    }

    get fileSize() {
        return this._fileSize || 0;
    }

    get newContent() {
        throw "Invalid operation";
    }

    get newThumbnail() {
        throw "Invalid operation";
    }

    get flags() {
        return this._flags;
    }

    get description() {
        return this._description;
    }

    get tags() {
        return this._tags;
    }

    get tagNames() {
        return this._tags.map((tag) => tag.names[0]);
    }

    get notes() {
        return this._notes;
    }

    get comments() {
        return this._comments;
    }

    get relations() {
        return this._relations;
    }

    get pools() {
        return this._pools;
    }

    get score() {
        return this._score;
    }

    get commentCount() {
        return this._commentCount;
    }

    get favoriteCount() {
        return this._favoriteCount;
    }

    get ownFavorite() {
        return this._ownFavorite;
    }

    get ownScore() {
        return this._ownScore;
    }

    get hasCustomThumbnail() {
        return this._hasCustomThumbnail;
    }

    set flags(value) {
        this._flags = value;
    }

    set description(value) {
        this._description = value;
    }

    set safety(value) {
        this._safety = value;
    }

    set relations(value) {
        this._relations = value;
    }

    set newContent(value) {
        this._newContent = value;
    }

    set newThumbnail(value) {
        this._newThumbnail = value;
    }

    set source(value) {
        this._source = value;
    }

    static fromResponse(response) {
        const ret = new Post();
        ret._updateFromResponse(response);
        return ret;
    }

    static reverseSearch(content) {
        let apiPromise = api.post(
            uri.formatApiLink("posts", "reverse-search"),
            {},
            { content: content }
        );
        let returnedPromise = apiPromise.then((response) => {
            if (response.exactPost) {
                response.exactPost = Post.fromResponse(response.exactPost);
            }
            for (let item of response.similarPosts) {
                item.post = Post.fromResponse(item.post);
            }
            return Promise.resolve(response);
        });
        returnedPromise.abort = () => apiPromise.abort();
        return returnedPromise;
    }

    static get(id) {
        return api.get(uri.formatApiLink("post", id)).then((response) => {
            return Promise.resolve(Post.fromResponse(response));
        });
    }

    _savePoolPosts() {
        const difference = (a, b) => a.filter((post) => !b.hasPoolId(post.id));

        // find the pools where the post was added or removed
        const added = difference(this.pools, this._orig._pools);
        const removed = difference(this._orig._pools, this.pools);

        let ops = [];

        // update each pool's list of posts
        for (let pool of added) {
            let op = Pool.get(pool.id).then((response) => {
                if (!response.posts.hasPostId(this._id)) {
                    response.posts.addById(this._id);
                    return response.save();
                } else {
                    return Promise.resolve(response);
                }
            });
            ops.push(op);
        }

        for (let pool of removed) {
            let op = Pool.get(pool.id).then((response) => {
                if (response.posts.hasPostId(this._id)) {
                    response.posts.removeById(this._id);
                    return response.save();
                } else {
                    return Promise.resolve(response);
                }
            });
            ops.push(op);
        }

        return Promise.all(ops);
    }

    save(anonymous) {
        const files = {};
        const detail = { version: this._version };

        // send only changed fields to avoid user privilege violation
        if (anonymous === true) {
            detail.anonymous = true;
        }
        if (this._safety !== this._orig._safety) {
            detail.safety = this._safety;
        }
        if (misc.arraysDiffer(this._flags, this._orig._flags)) {
            detail.flags = this._flags;
        }
        if (misc.arraysDiffer(this._tags, this._orig._tags)) {
            detail.tags = this._tags.map((tag) => tag.names[0]);
        }
        if (misc.arraysDiffer(this._relations, this._orig._relations)) {
            detail.relations = this._relations;
        }
        if (misc.arraysDiffer(this._notes, this._orig._notes)) {
            detail.notes = this._notes.map((note) => ({
                polygon: note.polygon.map((point) => [point.x, point.y]),
                text: note.text,
            }));
        }
        if (this._newContent) {
            files.content = this._newContent;
        }
        if (this._newThumbnail !== undefined) {
            files.thumbnail = this._newThumbnail;
        }
        if (this._source !== this._orig._source) {
            detail.source = this._source;
        }
        if (this._description !== this._orig._description) {
            detail.description = this._description;
        }

        let apiPromise = this._id
            ? api.put(uri.formatApiLink("post", this.id), detail, files)
            : api.post(uri.formatApiLink("posts"), detail, files);

        return apiPromise
            .then((response) => {
                if (misc.arraysDiffer(this._pools, this._orig._pools)) {
                    return this._savePoolPosts().then(() =>
                        Promise.resolve(response)
                    );
                }
                return Promise.resolve(response);
            })
            .then(
                (response) => {
                    this._updateFromResponse(response);
                    this.dispatchEvent(
                        new CustomEvent("change", { detail: { post: this } })
                    );
                    if (this._newContent) {
                        this.dispatchEvent(
                            new CustomEvent("changeContent", {
                                detail: { post: this },
                            })
                        );
                    }
                    if (this._newThumbnail) {
                        this.dispatchEvent(
                            new CustomEvent("changeThumbnail", {
                                detail: { post: this },
                            })
                        );
                    }

                    return Promise.resolve();
                },
                (error) => {
                    if (
                        error.response &&
                        error.response.name === "PostAlreadyUploadedError"
                    ) {
                        error.message = `Post already uploaded (@${error.response.otherPostId})`;
                    }
                    return Promise.reject(error);
                }
            );
    }

    feature() {
        return api
            .post(uri.formatApiLink("featured-post"), { id: this._id })
            .then((response) => {
                return Promise.resolve();
            });
    }

    delete() {
        return api
            .delete(uri.formatApiLink("post", this.id), {
                version: this._version,
            })
            .then((response) => {
                this.dispatchEvent(
                    new CustomEvent("delete", {
                        detail: {
                            post: this,
                        },
                    })
                );
                return Promise.resolve();
            });
    }

    merge(targetId, useOldContent) {
        return api
            .get(uri.formatApiLink("post", targetId))
            .then((response) => {
                return api.post(uri.formatApiLink("post-merge"), {
                    removeVersion: this._version,
                    remove: this._id,
                    mergeToVersion: response.version,
                    mergeTo: targetId,
                    replaceContent: useOldContent,
                });
            })
            .then((response) => {
                this._updateFromResponse(response);
                this.dispatchEvent(
                    new CustomEvent("change", {
                        detail: {
                            post: this,
                        },
                    })
                );
                return Promise.resolve();
            });
    }

    setScore(score) {
        return api
            .put(uri.formatApiLink("post", this.id, "score"), { score: score })
            .then((response) => {
                const prevFavorite = this._ownFavorite;
                this._updateFromResponse(response);
                if (this._ownFavorite !== prevFavorite) {
                    this.dispatchEvent(
                        new CustomEvent("changeFavorite", {
                            detail: {
                                post: this,
                            },
                        })
                    );
                }
                this.dispatchEvent(
                    new CustomEvent("changeScore", {
                        detail: {
                            post: this,
                        },
                    })
                );
                return Promise.resolve();
            });
    }

    addToFavorites() {
        return api
            .post(uri.formatApiLink("post", this.id, "favorite"))
            .then((response) => {
                const prevScore = this._ownScore;
                this._updateFromResponse(response);
                if (this._ownScore !== prevScore) {
                    this.dispatchEvent(
                        new CustomEvent("changeScore", {
                            detail: {
                                post: this,
                            },
                        })
                    );
                }
                this.dispatchEvent(
                    new CustomEvent("changeFavorite", {
                        detail: {
                            post: this,
                        },
                    })
                );
                return Promise.resolve();
            });
    }

    removeFromFavorites() {
        return api
            .delete(uri.formatApiLink("post", this.id, "favorite"))
            .then((response) => {
                const prevScore = this._ownScore;
                this._updateFromResponse(response);
                if (this._ownScore !== prevScore) {
                    this.dispatchEvent(
                        new CustomEvent("changeScore", {
                            detail: {
                                post: this,
                            },
                        })
                    );
                }
                this.dispatchEvent(
                    new CustomEvent("changeFavorite", {
                        detail: {
                            post: this,
                        },
                    })
                );
                return Promise.resolve();
            });
    }

    mutateContentUrl() {
        this._contentUrl =
            this._orig._contentUrl +
            "?bypass-cache=" +
            Math.round(Math.random() * 1000);
    }

    _updateFromResponse(response) {
        const map = () => ({
            _version: response.version,
            _id: response.id,
            _type: response.type,
            _mimeType: response.mimeType,
            _checksumSHA1: response.checksum,
            _checksumMD5: response.checksumMD5,
            _creationTime: response.creationTime,
            _user: response.user,
            _safety: response.safety,
            _contentUrl: response.contentUrl,
            _fullContentUrl: new URL(
                response.contentUrl,
                document.getElementsByTagName("base")[0].href
            ).href,
            _thumbnailUrl: response.thumbnailUrl,
            _source: response.source,
            _canvasWidth: response.canvasWidth,
            _canvasHeight: response.canvasHeight,
            _fileSize: response.fileSize,

            _flags: [...(response.flags || [])],
            _description: response.description,
            _relations: [...(response.relations || [])],

            _score: response.score,
            _commentCount: response.commentCount,
            _favoriteCount: response.favoriteCount,
            _ownScore: response.ownScore,
            _ownFavorite: response.ownFavorite,
            _hasCustomThumbnail: response.hasCustomThumbnail,
        });

        for (let obj of [this, this._orig]) {
            obj._tags.sync(response.tags);
            obj._notes.sync(response.notes);
            obj._comments.sync(response.comments);
            obj._pools.sync(response.pools);
        }

        Object.assign(this, map());
        Object.assign(this._orig, map());
    }
}

module.exports = Post;
