'use strict';

const router = require('../router.js');
const api = require('../api.js');
const misc = require('../util/misc.js');
const settings = require('../models/settings.js');
const Comment = require('../models/comment.js');
const Post = require('../models/post.js');
const PostList = require('../models/post_list.js');
const topNavigation = require('../models/top_navigation.js');
const PostView = require('../views/post_view.js');
const EmptyView = require('../views/empty_view.js');

class PostController {
    constructor(id, editMode, ctx) {
        topNavigation.activate('posts');
        topNavigation.setTitle('Post #' + id.toString());

        let parameters = ctx.parameters;
        Promise.all([
                Post.get(id),
                PostList.getAround(
                    id, this._decorateSearchQuery(
                        parameters ? parameters.query : '')),
        ]).then(responses => {
            const [post, aroundResponse] = responses;

            // remove junk from query, but save it into history so that it can
            // be still accessed after history navigation / page refresh
            if (parameters.query) {
                ctx.state.parameters = parameters;
                const url = editMode ?
                    '/post/' + id + '/edit' :
                    '/post/' + id;
                router.replace(url, ctx.state, false);
            }

            this._post = post;
            this._view = new PostView({
                post: post,
                editMode: editMode,
                nextPostId: aroundResponse.next ? aroundResponse.next.id : null,
                prevPostId: aroundResponse.prev ? aroundResponse.prev.id : null,
                canEditPosts: api.hasPrivilege('posts:edit'),
                canListComments: api.hasPrivilege('comments:list'),
                canCreateComments: api.hasPrivilege('comments:create'),
                parameters: parameters,
            });
            if (this._view.sidebarControl) {
                this._view.sidebarControl.addEventListener(
                    'favorite', e => this._evtFavoritePost(e));
                this._view.sidebarControl.addEventListener(
                    'unfavorite', e => this._evtUnfavoritePost(e));
                this._view.sidebarControl.addEventListener(
                    'score', e => this._evtScorePost(e));
                this._view.sidebarControl.addEventListener(
                    'fitModeChange', e => this._evtFitModeChange(e));
                this._view.sidebarControl.addEventListener(
                    'change', e => this._evtPostChange(e));
                this._view.sidebarControl.addEventListener(
                    'submit', e => this._evtPostEdit(e));
                this._view.sidebarControl.addEventListener(
                    'feature', e => this._evtPostFeature(e));
            }
            if (this._view.commentFormControl) {
                this._view.commentFormControl.addEventListener(
                    'change', e => this._evtCommentChange(e));
                this._view.commentFormControl.addEventListener(
                    'submit', e => this._evtCreateComment(e));
            }
            if (this._view.commentListControl) {
                this._view.commentListControl.addEventListener(
                    'change', e => this._evtUpdateComment(e));
                this._view.commentListControl.addEventListener(
                    'score', e => this._evtScoreComment(e));
                this._view.commentListControl.addEventListener(
                    'delete', e => this._evtDeleteComment(e));
            }
        }, errorMessage => {
            this._view = new EmptyView();
            this._view.showError(errorMessage);
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

    _evtFitModeChange(e) {
        const browsingSettings = settings.get();
        browsingSettings.fitMode = e.detail.mode;
        settings.save(browsingSettings);
    }

    _evtPostFeature(e) {
        this._view.sidebarControl.disableForm();
        this._view.sidebarControl.clearMessages();
        e.detail.post.feature()
            .then(() => {
                this._view.sidebarControl.showSuccess('Post featured.');
                this._view.sidebarControl.enableForm();
            }, errorMessage => {
                this._view.sidebarControl.showError(errorMessage);
                this._view.sidebarControl.enableForm();
            });
    }

    _evtPostEdit(e) {
        this._view.sidebarControl.disableForm();
        this._view.sidebarControl.clearMessages();
        const post = e.detail.post;
        if (e.detail.tags !== undefined) {
            post.tags = e.detail.tags;
        }
        if (e.detail.safety !== undefined) {
            post.safety = e.detail.safety;
        }
        if (e.detail.flags !== undefined) {
            post.flags = e.detail.flags;
        }
        if (e.detail.relations !== undefined) {
            post.relations = e.detail.relations;
        }
        if (e.detail.content !== undefined) {
            post.content = e.detail.content;
        }
        if (e.detail.thumbnail !== undefined) {
            post.thumbnail = e.detail.thumbnail;
        }
        post.save()
            .then(() => {
                this._view.sidebarControl.showSuccess('Post saved.');
                this._view.sidebarControl.enableForm();
                misc.disableExitConfirmation();
            }, errorMessage => {
                this._view.sidebarControl.showError(errorMessage);
                this._view.sidebarControl.enableForm();
            });
    }

    _evtPostChange(e) {
        misc.enableExitConfirmation();
    }

    _evtCommentChange(e) {
        misc.enableExitConfirmation();
    }

    _evtCreateComment(e) {
        // TODO: disable form
        const comment = Comment.create(this._post.id);
        comment.text = e.detail.text;
        comment.save()
            .then(() => {
                this._post.comments.add(comment);
                this._view.commentFormControl.setText('');
                // TODO: enable form
                misc.disableExitConfirmation();
            }, errorMessage => {
                this._view.commentFormControl.showError(errorMessage);
                // TODO: enable form
            });
    }

    _evtUpdateComment(e) {
        // TODO: disable form
        e.detail.comment.text = e.detail.text;
        e.detail.comment.save()
            .catch(errorMessage => {
                e.detail.target.showError(errorMessage);
                // TODO: enable form
            });
    }

    _evtScoreComment(e) {
        e.detail.comment.setScore(e.detail.score)
            .catch(errorMessage => {
                window.alert(errorMessage);
            });
    }

    _evtDeleteComment(e) {
        e.detail.comment.delete()
            .catch(errorMessage => {
                window.alert(errorMessage);
            });
    }

    _evtScorePost(e) {
        e.detail.post.setScore(e.detail.score)
            .catch(errorMessage => {
                window.alert(errorMessage);
            });
    }

    _evtFavoritePost(e) {
        e.detail.post.addToFavorites()
            .catch(errorMessage => {
                window.alert(errorMessage);
            });
    }

    _evtUnfavoritePost(e) {
        e.detail.post.removeFromFavorites()
            .catch(errorMessage => {
                window.alert(errorMessage);
            });
    }
}

module.exports = router => {
    router.enter('/post/:id/edit/:parameters?',
        (ctx, next) => { misc.parseUrlParametersRoute(ctx, next); },
        (ctx, next) => {
            // restore parameters from history state
            if (ctx.state.parameters) {
                Object.assign(ctx.parameters, ctx.state.parameters);
            }
            ctx.controller = new PostController(ctx.parameters.id, true, ctx);
        });
    router.enter(
        '/post/:id/:parameters?',
        (ctx, next) => { misc.parseUrlParametersRoute(ctx, next); },
        (ctx, next) => {
            // restore parameters from history state
            if (ctx.state.parameters) {
                Object.assign(ctx.parameters, ctx.state.parameters);
            }
            ctx.controller = new PostController(ctx.parameters.id, false, ctx);
        });
};
