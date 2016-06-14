'use strict';

const api = require('../api.js');
const settings = require('../models/settings.js');
const misc = require('../util/misc.js');
const topNavigation = require('../models/top_navigation.js');
const PageController = require('../controllers/page_controller.js');
const PostsHeaderView = require('../views/posts_header_view.js');
const PostsPageView = require('../views/posts_page_view.js');

class PostListController {
    constructor(ctx) {
        topNavigation.activate('posts');

        this._pageController = new PageController({
            searchQuery: ctx.searchQuery,
            clientUrl: '/posts/' + misc.formatSearchQuery({
                text: ctx.searchQuery.text, page: '{page}'}),
            requestPage: PageController.createHistoryCacheProxy(
                ctx,
                page => {
                    const text
                        = this._decorateSearchQuery(ctx.searchQuery.text);
                    return api.get(
                        `/posts/?query=${text}&page=${page}&pageSize=40` +
                        '&fields=id,type,tags,score,favoriteCount,' +
                        'commentCount,thumbnailUrl');
                }),
            headerRenderer: headerCtx => {
                return new PostsHeaderView(headerCtx);
            },
            pageRenderer: pageCtx => {
                Object.assign(pageCtx, {
                    canViewPosts: api.hasPrivilege('posts:view'),
                });
                return new PostsPageView(pageCtx);
            },
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
    router.enter(
        '/posts/:query?',
        (ctx, next) => { misc.parseSearchQueryRoute(ctx, next); },
        (ctx, next) => { ctx.controller = new PostListController(ctx); });
};
