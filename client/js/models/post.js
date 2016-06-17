'use strict';

const api = require('../api.js');
const events = require('../events.js');
const CommentList = require('./comment_list.js');

class Post extends events.EventTarget {
    constructor() {
        super();
        this._id = null;
        this._type = null;
        this._mimeType = null;
        this._creationTime = null;
        this._user = null;
        this._safety = null;
        this._contentUrl = null;
        this._thumbnailUrl = null;
        this._canvasWidth = null;
        this._canvasHeight = null;
        this._fileSize = null;

        this._tags = [];
        this._notes = [];
        this._comments = [];
        this._relations = [];

        this._score = null;
        this._favoriteCount = null;
        this._ownScore = null;
        this._ownFavorite = null;
    }

    static fromResponse(response) {
        const post = new Post();
        post._updateFromResponse(response);
        return post;
    }

    static get(id) {
        return api.get('/post/' + id)
            .then(response => {
                const post = Post.fromResponse(response);
                return Promise.resolve(post);
            }, response => {
                return Promise.reject(response);
            });
    }

    get id() { return this._id; }
    get type() { return this._type; }
    get mimeType() { return this._mimeType; }
    get creationTime() { return this._creationTime; }
    get user() { return this._user; }
    get safety() { return this._safety; }
    get contentUrl() { return this._contentUrl; }
    get thumbnailUrl() { return this._thumbnailUrl; }
    get canvasWidth() { return this._canvasWidth || 800; }
    get canvasHeight() { return this._canvasHeight || 450; }
    get fileSize() { return this._fileSize || 0; }

    get tags() { return this._tags; }
    get notes() { return this._notes; }
    get comments() { return this._comments; }
    get relations() { return this._relations; }

    get score() { return this._score; }
    get favoriteCount() { return this._favoriteCount; }
    get ownFavorite() { return this._ownFavorite; }
    get ownScore() { return this._ownScore; }

    setScore(score) {
        return api.put('/post/' + this._id + '/score', {score: score})
            .then(response => {
                const prevFavorite = this._ownFavorite;
                this._updateFromResponse(response);
                if (this._ownFavorite !== prevFavorite) {
                    this.dispatchEvent(new CustomEvent('changeFavorite', {
                        details: {
                            post: this,
                        },
                    }));
                }
                this.dispatchEvent(new CustomEvent('changeScore', {
                    details: {
                        post: this,
                    },
                }));
                return Promise.resolve();
            }, response => {
                return Promise.reject(response.description);
            });
    }

    addToFavorites() {
        return api.post('/post/' + this.id + '/favorite')
            .then(response => {
                const prevScore = this._ownScore;
                this._updateFromResponse(response);
                if (this._ownScore !== prevScore) {
                    this.dispatchEvent(new CustomEvent('changeScore', {
                        details: {
                            post: this,
                        },
                    }));
                }
                this.dispatchEvent(new CustomEvent('changeFavorite', {
                    details: {
                        post: this,
                    },
                }));
                return Promise.resolve();
            }, response => {
                return Promise.reject(response.description);
            });
    }

    removeFromFavorites() {
        return api.delete('/post/' + this.id + '/favorite')
            .then(response => {
                const prevScore = this._ownScore;
                this._updateFromResponse(response);
                if (this._ownScore !== prevScore) {
                    this.dispatchEvent(new CustomEvent('changeScore', {
                        details: {
                            post: this,
                        },
                    }));
                }
                this.dispatchEvent(new CustomEvent('changeFavorite', {
                    details: {
                        post: this,
                    },
                }));
                return Promise.resolve();
            }, response => {
                return Promise.reject(response.description);
            });
    }

    _updateFromResponse(response) {
        this._id = response.id;
        this._type = response.type;
        this._mimeType = response.mimeType;
        this._creationTime = response.creationTime;
        this._user = response.user;
        this._safety = response.safety;
        this._contentUrl = response.contentUrl;
        this._thumbnailUrl = response.thumbnailUrl;
        this._canvasWidth = response.canvasWidth;
        this._canvasHeight = response.canvasHeight;
        this._fileSize = response.fileSize;

        this._tags = response.tags;
        this._notes = response.notes;
        this._comments = CommentList.fromResponse(response.comments);
        this._relations = response.relations;

        this._score = response.score;
        this._favoriteCount = response.favoriteCount;
        this._ownScore = response.ownScore;
        this._ownFavorite = response.ownFavorite;
    }
};

module.exports = Post;
