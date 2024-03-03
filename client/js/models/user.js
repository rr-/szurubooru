"use strict";

const api = require("../api.js");
const uri = require("../util/uri.js");
const events = require("../events.js");
const misc = require("../util/misc.js");

class User extends events.EventTarget {
    constructor() {
        const TagList = require("./tag_list.js");

        super();
        this._orig = {};

        for (let obj of [this, this._orig]) {
            obj._blocklist = new TagList();
        }

        this._updateFromResponse({});
    }

    get name() {
        return this._name;
    }

    get rank() {
        return this._rank;
    }

    get email() {
        return this._email;
    }

    get avatarStyle() {
        return this._avatarStyle;
    }

    get avatarUrl() {
        return this._avatarUrl;
    }

    get creationTime() {
        return this._creationTime;
    }

    get lastLoginTime() {
        return this._lastLoginTime;
    }

    get commentCount() {
        return this._commentCount;
    }

    get favoritePostCount() {
        return this._favoritePostCount;
    }

    get uploadedPostCount() {
        return this._uploadedPostCount;
    }

    get likedPostCount() {
        return this._likedPostCount;
    }

    get dislikedPostCount() {
        return this._dislikedPostCount;
    }

    get rankName() {
        return api.rankNames.get(this.rank);
    }

    get avatarContent() {
        throw "Invalid operation";
    }

    get password() {
        throw "Invalid operation";
    }

	get blocklist() {
		return this._blocklist;
	}

    set name(value) {
        this._name = value;
    }

    set rank(value) {
        this._rank = value;
    }

    set email(value) {
        this._email = value || null;
    }

    set avatarStyle(value) {
        this._avatarStyle = value;
    }

    set avatarContent(value) {
        this._avatarContent = value;
    }

    set password(value) {
        this._password = value;
    }

	set blocklist(value) {
		this._blocklist = value || "";
	}

    static fromResponse(response) {
        const ret = new User();
        ret._updateFromResponse(response);
        return ret;
    }

    static get(name) {
        return api.get(uri.formatApiLink("user", name)).then((response) => {
            return Promise.resolve(User.fromResponse(response));
        });
    }

    save() {
        const files = [];
        const detail = { version: this._version };
        const transient = this._orig._name;

        if (this._name !== this._orig._name) {
            detail.name = this._name;
        }
        if (this._email !== this._orig._email) {
            detail.email = this._email;
        }
        if (this._rank !== this._orig._rank) {
            detail.rank = this._rank;
        }
        if (misc.arraysDiffer(this._blocklist, this._orig._blocklist)) {
            detail.blocklist = this._blocklist.map(
                (relation) => relation.names[0]
            );
        }
        if (this._avatarStyle !== this._orig._avatarStyle) {
            detail.avatarStyle = this._avatarStyle;
        }
        if (this._avatarContent) {
            detail.avatarStyle = this._avatarStyle;
            files.avatar = this._avatarContent;
        }
        if (this._password) {
            detail.password = this._password;
        }

        let promise = this._orig._name
            ? api.put(
                  uri.formatApiLink("user", this._orig._name),
                  detail,
                  files
              )
            : api.post(uri.formatApiLink("users"), detail, files);

        return promise.then((response) => {
            this._updateFromResponse(response);
            this.dispatchEvent(
                new CustomEvent("change", {
                    detail: {
                        user: this,
                    },
                })
            );
            return Promise.resolve();
        });
    }

    delete() {
        return api
            .delete(uri.formatApiLink("user", this._orig._name), {
                version: this._version,
            })
            .then((response) => {
                this.dispatchEvent(
                    new CustomEvent("delete", {
                        detail: {
                            user: this,
                        },
                    })
                );
                return Promise.resolve();
            });
    }

    _updateFromResponse(response) {
        const map = {
            _version: response.version,
            _name: response.name,
            _rank: response.rank,
            _email: response.email,
            _avatarStyle: response.avatarStyle,
            _avatarUrl: response.avatarUrl,
            _creationTime: response.creationTime,
            _lastLoginTime: response.lastLoginTime,
            _commentCount: response.commentCount,
            _favoritePostCount: response.favoritePostCount,
            _uploadedPostCount: response.uploadedPostCount,
            _likedPostCount: response.likedPostCount,
            _dislikedPostCount: response.dislikedPostCount,
        };

        for (let obj of [this, this._orig]) {
            obj._blocklist.sync(response.blocklist);
        }

        Object.assign(this, map);
        Object.assign(this._orig, map);

        this._password = null;
        this._avatarContent = null;
    }
}

module.exports = User;
