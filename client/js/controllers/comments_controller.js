'use strict';

const api = require('../api.js');
const misc = require('../util/misc.js');
const topNavigation = require('../models/top_navigation.js');
const PageController = require('../controllers/page_controller.js');
const CommentsPageView = require('../views/comments_page_view.js');

class CommentsController {
    constructor(ctx) {
        topNavigation.activate('comments');

        this._pageController = new PageController({
            searchQuery: ctx.searchQuery,
            clientUrl: '/comments/' + misc.formatSearchQuery({page: '{page}'}),
            requestPage: PageController.createHistoryCacheProxy(
                ctx,
                page => {
                    return api.get(
                        '/posts/?query=sort:comment-date+comment-count-min:1' +
                        `&page=${page}&pageSize=10&fields=` +
                        'id,comments,commentCount,thumbnailUrl');
                }),
            pageRenderer: pageCtx => {
                Object.assign(pageCtx, {
                    canViewPosts: api.hasPrivilege('posts:view'),
                });
                return new CommentsPageView(pageCtx);
            },
        });
    }
};

module.exports = router => {
    router.enter('/comments/:query?',
        (ctx, next) => { misc.parseSearchQueryRoute(ctx, next); },
        (ctx, next) => { new CommentsController(ctx); });
};
