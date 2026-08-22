"use strict";

const api = require("../api.js");
const events = require("../events.js");
const views = require("../util/views.js");
const uri = require("../util/uri.js");
const misc = require("../util/misc.js");

const template = views.getTemplate("post-readonly-sidebar");
const scoreTemplate = views.getTemplate("score");
const favTemplate = views.getTemplate("fav");

class PostReadonlySidebarControl extends events.EventTarget {
    constructor(hostNode, post, postContentControl) {
        super();
        this._hostNode = hostNode;
        this._post = post;
        this._postContentControl = postContentControl;

        post.addEventListener("changeFavorite", (e) => this._evtChangeFav(e));
        post.addEventListener("changeScore", (e) => this._evtChangeScore(e));

        views.replaceContent(
            this._hostNode,
            template({
                post: this._post,
                enableSafety: api.safetyEnabled(),
                canListPosts: api.hasPrivilege("posts:list"),
                canEditPosts: api.hasPrivilege("posts:edit"),
                canViewTags: api.hasPrivilege("tags:view"),
                escapeTagName: uri.escapeTagName,
                extractRootDomain: uri.extractRootDomain,
                getPrettyName: misc.getPrettyName,
            })
        );

        this._installFav();
        this._installScore();
        this._installFitButtons();
        this._syncFitButton();
    }

    get _scoreContainerNode() {
        return this._hostNode.querySelector(".score-container");
    }

    get _favContainerNode() {
        return this._hostNode.querySelector(".fav-container");
    }

    get _upvoteButtonNode() {
        return this._hostNode.querySelector(".upvote");
    }

    get _downvoteButtonNode() {
        return this._hostNode.querySelector(".downvote");
    }

    get _addFavButtonNode() {
        return this._hostNode.querySelector(".add-favorite");
    }

    get _remFavButtonNode() {
        return this._hostNode.querySelector(".remove-favorite");
    }

    get _fitBothButtonNode() {
        return this._hostNode.querySelector(".fit-both");
    }

    get _fitOriginalButtonNode() {
        return this._hostNode.querySelector(".fit-original");
    }

    get _fitWidthButtonNode() {
        return this._hostNode.querySelector(".fit-width");
    }

    get _fitHeightButtonNode() {
        return this._hostNode.querySelector(".fit-height");
    }

    _installFitButtons() {
        this._fitBothButtonNode.addEventListener(
            "click",
            this._eventZoomProxy(() => this._postContentControl.fitBoth())
        );
        this._fitOriginalButtonNode.addEventListener(
            "click",
            this._eventZoomProxy(() => this._postContentControl.fitOriginal())
        );
        this._fitWidthButtonNode.addEventListener(
            "click",
            this._eventZoomProxy(() => this._postContentControl.fitWidth())
        );
        this._fitHeightButtonNode.addEventListener(
            "click",
            this._eventZoomProxy(() => this._postContentControl.fitHeight())
        );
    }

    _installFav() {
        views.replaceContent(
            this._favContainerNode,
            favTemplate({
                favoriteCount: this._post.favoriteCount,
                ownFavorite: this._post.ownFavorite,
                canFavorite: api.hasPrivilege("posts:favorite"),
            })
        );

        if (this._addFavButtonNode) {
            this._addFavButtonNode.addEventListener("click", (e) =>
                this._evtAddToFavoritesClick(e)
            );
        }
        if (this._remFavButtonNode) {
            this._remFavButtonNode.addEventListener("click", (e) =>
                this._evtRemoveFromFavoritesClick(e)
            );
        }
    }

    _installScore() {
        views.replaceContent(
            this._scoreContainerNode,
            scoreTemplate({
                score: this._post.score,
                ownScore: this._post.ownScore,
                canScore: api.hasPrivilege("posts:score"),
            })
        );
        if (this._upvoteButtonNode) {
            this._upvoteButtonNode.addEventListener("click", (e) =>
                this._evtScoreClick(e, 1)
            );
        }
        if (this._downvoteButtonNode) {
            this._downvoteButtonNode.addEventListener("click", (e) =>
                this._evtScoreClick(e, -1)
            );
        }
    }

    _eventZoomProxy(func) {
        return (e) => {
            e.preventDefault();
            e.target.blur();
            func();
            this._syncFitButton();
            this.dispatchEvent(
                new CustomEvent("fitModeChange", {
                    detail: {
                        mode: this._getFitMode(),
                    },
                })
            );
        };
    }

    _getFitMode() {
        const funcToName = {};
        funcToName[this._postContentControl.fitBoth] = "fit-both";
        funcToName[this._postContentControl.fitOriginal] = "fit-original";
        funcToName[this._postContentControl.fitWidth] = "fit-width";
        funcToName[this._postContentControl.fitHeight] = "fit-height";
        return funcToName[this._postContentControl._currentFitFunction];
    }

    _syncFitButton() {
        const className = this._getFitMode();
        const oldNode = this._hostNode.querySelector(".zoom a.active");
        const newNode = this._hostNode.querySelector(`.zoom a.${className}`);
        if (oldNode) {
            oldNode.classList.remove("active");
        }
        newNode.classList.add("active");
        document.querySelector(".content").dataset.fit = className;
    }

    _evtAddToFavoritesClick(e) {
        e.preventDefault();
        this.dispatchEvent(
            new CustomEvent("favorite", {
                detail: {
                    post: this._post,
                },
            })
        );
    }

    _evtRemoveFromFavoritesClick(e) {
        e.preventDefault();
        this.dispatchEvent(
            new CustomEvent("unfavorite", {
                detail: {
                    post: this._post,
                },
            })
        );
    }

    _evtScoreClick(e, score) {
        e.preventDefault();
        this.dispatchEvent(
            new CustomEvent("score", {
                detail: {
                    post: this._post,
                    score: this._post.ownScore === score ? 0 : score,
                },
            })
        );
    }

    _evtChangeFav(e) {
        this._installFav();
    }

    _evtChangeScore(e) {
        this._installScore();
    }
}

module.exports = PostReadonlySidebarControl;
