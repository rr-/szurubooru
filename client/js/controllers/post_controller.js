'use strict';

const api = require('../api.js');
const settings = require('../models/settings.js');
const Post = require('../models/post.js');
const topNavigation = require('../models/top_navigation.js');
const PostView = require('../views/post_view.js');
const EmptyView = require('../views/empty_view.js');

class PostController {
    constructor(id, editMode) {
        topNavigation.activate('posts');

        Promise.all([
                Post.get(id),
                api.get(`/post/${id}/around?fields=id&query=` +
                    this._decorateSearchQuery('')),
        ]).then(responses => {
            const [post, aroundResponse] = responses;
            this._view = new PostView({
                post: post,
                editMode: editMode,
                nextPostId: aroundResponse.next ? aroundResponse.next.id : null,
                prevPostId: aroundResponse.prev ? aroundResponse.prev.id : null,
                canEditPosts: api.hasPrivilege('posts:edit'),
                canListComments: api.hasPrivilege('comments:list'),
                canCreateComments: api.hasPrivilege('comments:create'),
            });
        }, response => {
            this._view = new EmptyView();
            this._view.showError(response.description);
        });
    }

    _decorateSearchQuery(text) {
        const browsingSettings = settings.get();
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

module.exports = router => {
    router.enter('/post/:id', (ctx, next) => {
        ctx.controller = new PostController(ctx.params.id, false);
    });
    router.enter('/post/:id/edit', (ctx, next) => {
        ctx.controller = new PostController(ctx.params.id, true);
    });
};
