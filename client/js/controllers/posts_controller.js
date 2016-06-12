'use strict';

const router = require('../router.js');
const api = require('../api.js');
const settings = require('../settings.js');
const events = require('../events.js');
const misc = require('../util/misc.js');
const topNavController = require('../controllers/top_nav_controller.js');
const pageController = require('../controllers/page_controller.js');
const PostsHeaderView = require('../views/posts_header_view.js');
const PostsPageView = require('../views/posts_page_view.js');
const PostView = require('../views/post_view.js');
const EmptyView = require('../views/empty_view.js');

class PostsController {
    constructor() {
        this._postsHeaderView = new PostsHeaderView();
        this._postsPageView = new PostsPageView();
        this._postView = new PostView();
    }

    registerRoutes() {
        router.enter(
            '/upload',
            (ctx, next) => { this._uploadPostsRoute(); });
        router.enter(
            '/posts/:query?',
            (ctx, next) => { misc.parseSearchQueryRoute(ctx, next); },
            (ctx, next) => { this._listPostsRoute(ctx); });
        router.enter(
            '/post/:id',
            (ctx, next) => { this._showPostRoute(ctx.params.id, false); });
        router.enter(
            '/post/:id/edit',
            (ctx, next) => { this._showPostRoute(ctx.params.id, true); });
        this._emptyView = new EmptyView();
    }

    _uploadPostsRoute() {
        topNavController.activate('upload');
        this._emptyView.render();
    }

    _listPostsRoute(ctx) {
        topNavController.activate('posts');

        pageController.run({
            searchQuery: ctx.searchQuery,
            clientUrl: '/posts/' + misc.formatSearchQuery({
                text: ctx.searchQuery.text, page: '{page}'}),
            requestPage: pageController.createHistoryCacheProxy(
                ctx,
                page => {
                    const text = this._decorateSearchQuery(ctx.searchQuery.text);
                    return api.get(
                        `/posts/?query=${text}&page=${page}&pageSize=40` +
                        '&fields=id,type,tags,score,favoriteCount,' +
                        'commentCount,thumbnailUrl');
                }),
            headerRenderer: this._postsHeaderView,
            pageRenderer: this._postsPageView,
            pageContext: {
                canViewPosts: api.hasPrivilege('posts:view'),
            }
        });
    }

    _showPostRoute(id, editMode) {
        topNavController.activate('posts');
        Promise.all([
                api.get('/post/' + id),
                api.get(`/post/${id}/around?fields=id&query=`
                    + this._decorateSearchQuery('')),
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

module.exports = new PostsController();
