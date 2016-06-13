'use strict';

const router = require('../router.js');
const api = require('../api.js');
const settings = require('../settings.js');
const misc = require('../util/misc.js');
const pageController = require('../controllers/page_controller.js');
const TopNavigation = require('../models/top_navigation.js');
const PostsHeaderView = require('../views/posts_header_view.js');
const PostsPageView = require('../views/posts_page_view.js');

class PostListController {
    constructor() {
        this._postsHeaderView = new PostsHeaderView();
        this._postsPageView = new PostsPageView();
    }

    registerRoutes() {
        router.enter(
            '/posts/:query?',
            (ctx, next) => { misc.parseSearchQueryRoute(ctx, next); },
            (ctx, next) => { this._listPostsRoute(ctx); });
    }

    _listPostsRoute(ctx) {
        TopNavigation.activate('posts');

        pageController.run({
            searchQuery: ctx.searchQuery,
            clientUrl: '/posts/' + misc.formatSearchQuery({
                text: ctx.searchQuery.text, page: '{page}'}),
            requestPage: pageController.createHistoryCacheProxy(
                ctx,
                page => {
                    const text
                        = this._decorateSearchQuery(ctx.searchQuery.text);
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

module.exports = new PostListController();
