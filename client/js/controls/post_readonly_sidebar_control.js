'use strict';

const api = require('../api.js');
const tags = require('../tags.js');
const views = require('../util/views.js');

class PostReadonlySidebarControl {
    constructor(hostNode, post, postContentControl) {
        this._hostNode = hostNode;
        this._post = post;
        this._postContentControl = postContentControl;
        this._template = views.getTemplate('post-readonly-sidebar');
        this._scoreTemplate = views.getTemplate('score');
        this._favTemplate = views.getTemplate('fav');

        this.install();
    }

    install() {
        const sourceNode = this._template({
            post: this._post,
            getTagCategory: this._getTagCategory,
            getTagUsages: this._getTagUsages,
            canListPosts: api.hasPrivilege('posts:list'),
            canViewTags: api.hasPrivilege('tags:view'),
        });

        views.showView(
            sourceNode.querySelector('.score-container'),
            this._scoreTemplate({
                score: this._post.score,
                ownScore: this._post.ownScore,
                canScore: api.hasPrivilege('posts:score'),
            }));

        views.showView(
            sourceNode.querySelector('.fav-container'),
            this._favTemplate({
                favoriteCount: this._post.favoriteCount,
                ownFavorite: this._post.ownFavorite,
                canFavorite: api.hasPrivilege('posts:favorite'),
            }));

        const upvoteButton = sourceNode.querySelector('.upvote');
        const downvoteButton = sourceNode.querySelector('.downvote');
        const addFavButton = sourceNode.querySelector('.add-favorite');
        const remFavButton = sourceNode.querySelector('.remove-favorite');
        const fitBothButton = sourceNode.querySelector('.fit-both');
        const fitOriginalButton = sourceNode.querySelector('.fit-original');
        const fitWidthButton = sourceNode.querySelector('.fit-width');
        const fitHeightButton = sourceNode.querySelector('.fit-height');

        if (upvoteButton) {
            upvoteButton.addEventListener(
                'click', this._eventRequestProxy(
                    () => this._setScore(this._post.ownScore === 1 ? 0 : 1)));
        }
        if (downvoteButton) {
            downvoteButton.addEventListener(
                'click', this._eventRequestProxy(
                    () => this._setScore(this._post.ownScore === -1 ? 0 : -1)));
        }

        if (addFavButton) {
            addFavButton.addEventListener(
                'click', this._eventRequestProxy(
                    () => this._addToFavorites()));
        }
        if (remFavButton) {
            remFavButton.addEventListener(
                'click', this._eventRequestProxy(
                    () => this._removeFromFavorites()));
        }

        fitBothButton.addEventListener(
            'click', this._eventZoomProxy(
                () => this._postContentControl.fitBoth()));
        fitOriginalButton.addEventListener(
            'click', this._eventZoomProxy(
                () => this._postContentControl.fitOriginal()));
        fitWidthButton.addEventListener(
            'click', this._eventZoomProxy(
                () => this._postContentControl.fitWidth()));
        fitHeightButton.addEventListener(
            'click', this._eventZoomProxy(
                () => this._postContentControl.fitHeight()));

        views.showView(this._hostNode, sourceNode);

        this._syncFitButton();
    }

    _eventZoomProxy(func) {
        return e => {
            e.preventDefault();
            e.target.blur();
            func();
            this._syncFitButton();
        };
    }

    _eventRequestProxy(promise) {
        return e => {
            e.preventDefault();
            promise().then(() => {
                this.install();
            });
        };
    }

    _syncFitButton() {
        const funcToClassName = {};
        funcToClassName[this._postContentControl.fitBoth] = 'fit-both';
        funcToClassName[this._postContentControl.fitOriginal] = 'fit-original';
        funcToClassName[this._postContentControl.fitWidth] = 'fit-width';
        funcToClassName[this._postContentControl.fitHeight] = 'fit-height';
        const className = funcToClassName[
            this._postContentControl._currentFitFunction];
        const oldNode = this._hostNode.querySelector('.zoom a.active');
        const newNode = this._hostNode.querySelector(`.zoom a.${className}`);
        if (oldNode) {
            oldNode.classList.remove('active');
        }
        newNode.classList.add('active');
    }

    _getTagUsages(name) {
        const tag = tags.getTagByName(name);
        return tag ? tag.usages : 0;
    }

    _getTagCategory(name) {
        const tag = tags.getTagByName(name);
        return tag ? tag.category : 'unknown';
    }

    _setScore(score) {
        return this._requestAndRefresh(
            () => api.put('/post/' + this._post.id + '/score', {score: score}));
    }

    _addToFavorites() {
        return this._requestAndRefresh(
            () => api.post('/post/' + this._post.id + '/favorite'));
    }

    _removeFromFavorites() {
        return this._requestAndRefresh(
            () => api.delete('/post/' + this._post.id + '/favorite'));
    }

    _requestAndRefresh(requestPromise) {
        return new Promise((resolve, reject) => {
            requestPromise()
                .then(
                    response => { return api.get('/post/' + this._post.id); },
                    response => { return Promise.reject(response); })
                .then(
                    response => {
                        this._post = response;
                        resolve();
                    },
                    response => {
                        reject();
                        window.alert(response.description);
                    });
        });
    }
};

module.exports = PostReadonlySidebarControl;
