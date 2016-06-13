'use strict';

const router = require('../router.js');
const api = require('../api.js');
const events = require('../events.js');
const settings = require('../settings.js');
const TopNavigation = require('../models/top_navigation.js');
const PostView = require('../views/post_view.js');
const EmptyView = require('../views/empty_view.js');

class PostController {
    constructor() {
        this._postView = new PostView();
        this._emptyView = new EmptyView();
    }

    registerRoutes() {
        router.enter(
            '/post/:id',
            (ctx, next) => { this._showPostRoute(ctx.params.id, false); });
        router.enter(
            '/post/:id/edit',
            (ctx, next) => { this._showPostRoute(ctx.params.id, true); });
    }

    _showPostRoute(id, editMode) {
        TopNavigation.activate('posts');
        Promise.all([
                api.get('/post/' + id),
                api.get(`/post/${id}/around?fields=id&query=` +
                    this._decorateSearchQuery('')),
        ]).then(responses => {
            const [postResponse, aroundResponse] = responses;
            this._postView.render({
                post: postResponse,
                editMode: editMode,
                nextPostId: aroundResponse.next ? aroundResponse.next.id : null,
                prevPostId: aroundResponse.prev ? aroundResponse.prev.id : null,
                canEditPosts: api.hasPrivilege('posts:edit'),
                canListComments: api.hasPrivilege('comments:list'),
                canCreateComments: api.hasPrivilege('comments:create'),
            });
        }, response => {
            this._emptyView.render();
            events.notify(events.Error, response.description);
        });
    }

    _decorateSearchQuery(text) {
        const browsingSettings = settings.getSettings();
        let disabledSafety = [];
        for (let key of Object.keys(browsingSettings.listPosts)) {
            if (browsingSettings.listPosts[key] === false) {
                disabledSafety.push(key);
            }
        }
        if (disabledSafety.length) {
            text = `-rating:${disabledSafety.join(',')} ${text}`;
        }
        return text.trim();
    }
}

module.exports = new PostController();
