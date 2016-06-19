'use strict';

const api = require('../api.js');
const events = require('../events.js');

class User extends events.EventTarget {
    constructor() {
        super();
        this._name              = null;
        this._rank              = null;
        this._email             = null;
        this._avatarStyle       = null;
        this._avatarUrl         = null;
        this._creationTime      = null;
        this._lastLoginTime     = null;
        this._commentCount      = null;
        this._favoritePostCount = null;
        this._uploadedPostCount = null;
        this._likedPostCount    = null;
        this._dislikedPostCount = null;

        this._origName          = null;
        this._origEmail         = null;
        this._origRank          = null;
        this._origAvatarStyle   = null;

        this._password          = null;
        this._avatarContent     = null;
    }

    get name()               { return this._name; }
    get rank()               { return this._rank; }
    get email()              { return this._email; }
    get avatarStyle()        { return this._avatarStyle; }
    get avatarUrl()          { return this._avatarUrl; }
    get creationTime()       { return this._creationTime; }
    get lastLoginTime()      { return this._lastLoginTime; }
    get commentCount()       { return this._commentCount; }
    get favoritePostCount()  { return this._favoritePostCount; }
    get uploadedPostCount()  { return this._uploadedPostCount; }
    get likedPostCount()     { return this._likedPostCount; }
    get dislikedPostCount()  { return this._dislikedPostCount; }
    get rankName()           { return api.rankNames.get(this.rank); }
    get avatarContent()      { throw 'Invalid operation'; }
    get password()           { throw 'Invalid operation'; }

    set name(value)          { this._name = value; }
    set rank(value)          { this._rank = value; }
    set email(value)         { this._email = value || null; }
    set avatarStyle(value)   { this._avatarStyle = value; }
    set avatarContent(value) { this._avatarContent = value; }
    set password(value)      { this._password = value; }

    static fromResponse(response) {
        const ret = new User();
        ret._updateFromResponse(response);
        return ret;
    }

    static get(name) {
        return api.get('/user/' + name)
            .then(response => {
                return Promise.resolve(User.fromResponse(response));
            }, response => {
                return Promise.reject(response.description);
            });
    }

    save() {
        const files = [];
        const data = {};
        if (this.name !== this._origName) {
            data.name = this.name;
        }
        if (this._password) {
            data.password = this._password;
        }

        if (this.email !== this._origEmail) {
            data.email = this.email;
        }

        if (this.rank !== this._origRank) {
            data.rank = this.rank;
        }
        if (this.avatarStyle !== this._origAvatarStyle) {
            data.avatarStyle = this.avatarStyle;
        }
        if (this._avatarContent) {
            files.avatar = this._avatarContent;
        }

        let promise = this._origName ?
            api.put('/user/' + this._origName, data, files) :
            api.post('/users', data, files);

        return promise
            .then(response => {
                this._updateFromResponse(response);
                this.dispatchEvent(new CustomEvent('change', {
                    detail: {
                        user: this,
                    },
                }));
                return Promise.resolve();
            }, response => {
                return Promise.reject(response.description);
            });
    }

    delete() {
        return api.delete('/user/' + this._origName)
            .then(response => {
                this.dispatchEvent(new CustomEvent('delete', {
                    detail: {
                        user: this,
                    },
                }));
                return Promise.resolve();
            }, response => {
                return Promise.reject(response.description);
            });
    }

    _updateFromResponse(response) {
        this._name              = response.name;
        this._rank              = response.rank;
        this._email             = response.email;
        this._avatarStyle       = response.avatarStyle;
        this._avatarUrl         = response.avatarUrl;
        this._creationTime      = response.creationTime;
        this._lastLoginTime     = response.lastLoginTime;
        this._commentCount      = response.commentCount;
        this._favoritePostCount = response.favoritePostCount;
        this._uploadedPostCount = response.uploadedPostCount;
        this._likedPostCount    = response.likedPostCount;
        this._dislikedPostCount = response.dislikedPostCount;

        this._origName          = this.name;
        this._origRank          = this.rank;
        this._origEmail         = this.email;
        this._origAvatarStyle   = this.avatarStyle;

        this._password          = null;
        this._avatarContent     = null;
    }
};

module.exports = User;
