'use strict';

const api = require('../api.js');
const tags = require('../tags.js');
const events = require('../events.js');
const CommentList = require('./comment_list.js');

function _arraysDiffer(source1, source2) {
    return [...source1].filter(value => !source2.includes(value)).length > 0
        || [...source2].filter(value => !source1.includes(value)).length > 0;
}

class Post extends events.EventTarget {
    constructor() {
        super();
        this._orig          = {};

        this._id            = null;
        this._type          = null;
        this._mimeType      = null;
        this._creationTime  = null;
        this._user          = null;
        this._safety        = null;
        this._contentUrl    = null;
        this._thumbnailUrl  = null;
        this._canvasWidth   = null;
        this._canvasHeight  = null;
        this._fileSize      = null;

        this._tags          = [];
        this._notes         = [];
        this._comments      = [];
        this._relations     = [];

        this._score         = null;
        this._favoriteCount = null;
        this._ownScore      = null;
        this._ownFavorite   = null;
    }

    get id()             { return this._id; }
    get type()           { return this._type; }
    get mimeType()       { return this._mimeType; }
    get creationTime()   { return this._creationTime; }
    get user()           { return this._user; }
    get safety()         { return this._safety; }
    get contentUrl()     { return this._contentUrl; }
    get thumbnailUrl()   { return this._thumbnailUrl; }
    get canvasWidth()    { return this._canvasWidth || 800; }
    get canvasHeight()   { return this._canvasHeight || 450; }
    get fileSize()       { return this._fileSize || 0; }

    get tags()           { return this._tags; }
    get notes()          { return this._notes; }
    get comments()       { return this._comments; }
    get relations()      { return this._relations; }

    get score()          { return this._score; }
    get favoriteCount()  { return this._favoriteCount; }
    get ownFavorite()    { return this._ownFavorite; }
    get ownScore()       { return this._ownScore; }

    set tags(value)      { this._tags = value; }
    set safety(value)    { this._safety = value; }
    set relations(value) { this._relations = value; }

    static fromResponse(response) {
        const ret = new Post();
        ret._updateFromResponse(response);
        return ret;
    }

    static get(id) {
        return api.get('/post/' + id)
            .then(response => {
                return Promise.resolve(Post.fromResponse(response));
            }, response => {
                return Promise.reject(response.description);
            });
    }

    isTaggedWith(tagName) {
        return this._tags.map(s => s.toLowerCase()).includes(tagName);
    }

    addTag(tagName, addImplications) {
        if (this.isTaggedWith(tagName)) {
            return;
        }
        this._tags.push(tagName);
        if (addImplications !== false) {
            for (let otherTag of tags.getAllImplications(tagName)) {
                this.addTag(otherTag, addImplications);
            }
        }
    }

    removeTag(tagName) {
        this._tags = this._tags.filter(
            s => s.toLowerCase() != tagName.toLowerCase());
    }

    save() {
        const detail = {};

        // send only changed fields to avoid user privilege violation
        if (this._safety !== this._orig._safety) {
            detail.safety = this._safety;
        }
        if (_arraysDiffer(this._tags, this._orig._tags)) {
            detail.tags = this._tags;
        }
        if (_arraysDiffer(this._relations, this._orig._relations)) {
            detail.relations = this._relations;
        }

        let promise = this._id ?
            api.put('/post/' + this._id, detail) :
            api.post('/posts', detail);

        return promise.then(response => {
            this._updateFromResponse(response);
            this.dispatchEvent(
                new CustomEvent('change', {detail: {post: this}}));
            return Promise.resolve();
        }, response => {
            return Promise.reject(response.description);
        });
    }

    setScore(score) {
        return api.put('/post/' + this._id + '/score', {score: score})
            .then(response => {
                const prevFavorite = this._ownFavorite;
                this._updateFromResponse(response);
                if (this._ownFavorite !== prevFavorite) {
                    this.dispatchEvent(new CustomEvent('changeFavorite', {
                        detail: {
                            post: this,
                        },
                    }));
                }
                this.dispatchEvent(new CustomEvent('changeScore', {
                    detail: {
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
                        detail: {
                            post: this,
                        },
                    }));
                }
                this.dispatchEvent(new CustomEvent('changeFavorite', {
                    detail: {
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
                        detail: {
                            post: this,
                        },
                    }));
                }
                this.dispatchEvent(new CustomEvent('changeFavorite', {
                    detail: {
                        post: this,
                    },
                }));
                return Promise.resolve();
            }, response => {
                return Promise.reject(response.description);
            });
    }

    _updateFromResponse(response) {
        const map = {
            _id:            response.id,
            _type:          response.type,
            _mimeType:      response.mimeType,
            _creationTime:  response.creationTime,
            _user:          response.user,
            _safety:        response.safety,
            _contentUrl:    response.contentUrl,
            _thumbnailUrl:  response.thumbnailUrl,
            _canvasWidth:   response.canvasWidth,
            _canvasHeight:  response.canvasHeight,
            _fileSize:      response.fileSize,

            _tags:          response.tags,
            _notes:         response.notes,
            _comments:      CommentList.fromResponse(response.comments || []),
            _relations:     response.relations,

            _score:         response.score,
            _favoriteCount: response.favoriteCount,
            _ownScore:      response.ownScore,
            _ownFavorite:   response.ownFavorite,
        };

        Object.assign(this, map);
        Object.assign(this._orig, map);
    }
};

module.exports = Post;
