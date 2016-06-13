'use strict';

const api = require('../api.js');
const events = require('../events.js');

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

    // encapsulation - don't let set these casually
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

    static get(id) {
        return new Promise((resolve, reject) => {
            api.get('/post/' + id)
                .then(response => {
                    const post = new Post();
                    post._id = response.id;
                    post._type = response.type;
                    post._mimeType = response.mimeType;
                    post._creationTime = response.creationTime;
                    post._user = response.user;
                    post._safety = response.safety;
                    post._contentUrl = response.contentUrl;
                    post._thumbnailUrl = response.thumbnailUrl;
                    post._canvasWidth = response.canvasWidth;
                    post._canvasHeight = response.canvasHeight;
                    post._fileSize = response.fileSize;

                    post._tags = response.tags;
                    post._notes = response.notes;
                    post._comments = response.comments;
                    post._relations = response.relations;

                    post._score = response.score;
                    post._favoriteCount = response.favoriteCount;
                    post._ownScore = response.ownScore;
                    post._ownFavorite = response.ownFavorite;
                    resolve(post);
                }, response => {
                    reject(response);
                });
        });
    }
};

module.exports = Post;
