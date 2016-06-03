'use strict';

const page = require('page');
const api = require('../api.js');
const settings = require('../settings.js');
const misc = require('../util/misc.js');
const topNavController = require('../controllers/top_nav_controller.js');
const pageController = require('../controllers/page_controller.js');
const PostsHeaderView = require('../views/posts_header_view.js');
const PostsPageView = require('../views/posts_page_view.js');
const EmptyView = require('../views/empty_view.js');

class PostsController {
    constructor() {
        this._postsHeaderView = new PostsHeaderView();
        this._postsPageView = new PostsPageView();
    }

    registerRoutes() {
        page('/upload', (ctx, next) => { this._uploadPostsRoute(); });
        page('/posts/:query?',
            (ctx, next) => { misc.parseSearchQueryRoute(ctx, next); },
            (ctx, next) => { this._listPostsRoute(ctx); });
        page(
            '/post/:id',
            (ctx, next) => { this._showPostRoute(ctx.params.id); });
        page(
            '/post/:id/edit',
            (ctx, next) => { this._editPostRoute(ctx.params.id); });
        this._emptyView = new EmptyView();
    }

    _uploadPostsRoute() {
        topNavController.activate('upload');
        this._emptyView.render();
    }

    _listPostsRoute(ctx) {
        topNavController.activate('posts');

        pageController.run({
            state: ctx.state,
            requestPage: page => {
                const browsingSettings = settings.getSettings();
                let text = ctx.searchQuery.text;
                let disabledSafety = [];
                for (let key of Object.keys(browsingSettings.listPosts)) {
                    if (browsingSettings.listPosts[key] === false) {
                        disabledSafety.push(key);
                    }
                }
                if (disabledSafety.length) {
                    text = `-rating:${disabledSafety.join(',')} ${text}`;
                }
                text = text.trim();
                return api.get(
                    `/posts/?query=${text}&page=${page}&pageSize=40&_fields=` +
                    `id,type,tags,score,favoriteCount,` +
                    `commentCount,thumbnailUrl`);
            },
            clientUrl: '/posts/' + misc.formatSearchQuery({
                text: ctx.searchQuery.text, page: '{page}'}),
            searchQuery: ctx.searchQuery,
            headerRenderer: this._postsHeaderView,
            pageRenderer: this._postsPageView,
        });
    }

    _showPostRoute(id) {
        topNavController.activate('posts');
        this._emptyView.render();
    }

    _editPostRoute(id) {
        topNavController.activate('posts');
        this._emptyView.render();
    }
}

module.exports = new PostsController();
