'use strict';

const api = require('../api.js');
const tags = require('../tags.js');
const events = require('../events.js');
const NoteList = require('./note_list.js');
const CommentList = require('./comment_list.js');
const misc = require('../util/misc.js');

function _syncObservableCollection(target, plainList) {
    target.clear();
    for (let item of (plainList || [])) {
        target.add(target.constructor._itemClass.fromResponse(item));
    }
}

class Post extends events.EventTarget {
    constructor() {
        super();
        this._orig = {};

        for (let obj of [this, this._orig]) {
            obj._notes = new NoteList();
            obj._comments = new CommentList();
        }

        this._updateFromResponse({});
    }

    get id()                 { return this._id; }
    get type()               { return this._type; }
    get mimeType()           { return this._mimeType; }
    get creationTime()       { return this._creationTime; }
    get user()               { return this._user; }
    get safety()             { return this._safety; }
    get contentUrl()         { return this._contentUrl; }
    get thumbnailUrl()       { return this._thumbnailUrl; }
    get canvasWidth()        { return this._canvasWidth || 800; }
    get canvasHeight()       { return this._canvasHeight || 450; }
    get fileSize()           { return this._fileSize || 0; }
    get newContent()         { throw 'Invalid operation'; }
    get newContentUrl()      { throw 'Invalid operation'; }
    get newThumbnail()       { throw 'Invalid operation'; }

    get flags()              { return this._flags; }
    get tags()               { return this._tags; }
    get notes()              { return this._notes; }
    get comments()           { return this._comments; }
    get relations()          { return this._relations; }

    get score()              { return this._score; }
    get commentCount()       { return this._commentCount; }
    get favoriteCount()      { return this._favoriteCount; }
    get ownFavorite()        { return this._ownFavorite; }
    get ownScore()           { return this._ownScore; }
    get hasCustomThumbnail() { return this._hasCustomThumbnail; }

    set flags(value)         { this._flags = value; }
    set tags(value)          { this._tags = value; }
    set safety(value)        { this._safety = value; }
    set relations(value)     { this._relations = value; }
    set newContent(value)    { this._newContent = value; }
    set newContentUrl(value) { this._newContentUrl = value; }
    set newThumbnail(value)  { this._newThumbnail = value; }

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

    save(anonymous) {
        const files = [];
        const detail = {version: this._version};

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
            detail.tags = this._tags;
        }
        if (misc.arraysDiffer(this._relations, this._orig._relations)) {
            detail.relations = this._relations;
        }
        if (misc.arraysDiffer(this._notes, this._orig._notes)) {
            detail.notes = [...this._notes].map(note => ({
                polygon: [...note.polygon].map(
                    point => [point.x, point.y]),
                text: note.text,
            }));
        }
        if (this._newContent) {
            files.content = this._newContent;
        } else if (this._newContentUrl) {
            detail.contentUrl = this._newContentUrl;
        }
        if (this._newThumbnail !== undefined) {
            files.thumbnail = this._newThumbnail;
        }

        let apiPromise = this._id ?
            api.put('/post/' + this._id, detail, files) :
            api.post('/posts', detail, files);

        let returnedPromise = apiPromise.then(response => {
            this._updateFromResponse(response);
            this.dispatchEvent(
                new CustomEvent('change', {detail: {post: this}}));
            if (this._newContent || this._newContentUrl) {
                this.dispatchEvent(
                    new CustomEvent('changeContent', {detail: {post: this}}));
            }
            if (this._newThumbnail) {
                this.dispatchEvent(
                    new CustomEvent('changeThumbnail', {detail: {post: this}}));
            }
            return Promise.resolve();
        }, response => {
            if (response.name === 'PostAlreadyUploadedError') {
                return Promise.reject(
                    `Post already uploaded (@${response.otherPostId})`);
            }
            return Promise.reject(response.description);
        });

        returnedPromise.abort = () => {
            apiPromise.abort();
        };

        return returnedPromise;
    }

    feature() {
        return api.post('/featured-post', {id: this._id})
            .then(response => {
                return Promise.resolve();
            }, response => {
                return Promise.reject(response.description);
            });
    }

    delete() {
        return api.delete('/post/' + this._id, {version: this._version})
            .then(response => {
                this.dispatchEvent(new CustomEvent('delete', {
                    detail: {
                        post: this,
                    },
                }));
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

    mutateContentUrl() {
        this._contentUrl =
            this._orig._contentUrl +
            '?bypass-cache=' +
            Math.round(Math.random() * 1000);
    }

    _updateFromResponse(response) {
        const map = () => ({
            _version:       response.version,
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

            _flags:         [...response.flags || []],
            _tags:          [...response.tags || []],
            _relations:     [...response.relations || []],

            _score:         response.score,
            _commentCount:  response.commentCount,
            _favoriteCount: response.favoriteCount,
            _ownScore:      response.ownScore,
            _ownFavorite:   response.ownFavorite,
            _hasCustomThumbnail: response.hasCustomThumbnail,
        });

        for (let obj of [this, this._orig]) {
            _syncObservableCollection(obj._notes, response.notes);
            _syncObservableCollection(obj._comments, response.comments);
        }

        Object.assign(this, map());
        Object.assign(this._orig, map());
    }
};

module.exports = Post;
