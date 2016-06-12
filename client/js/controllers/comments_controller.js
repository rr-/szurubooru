'use strict';

const api = require('../api.js');
const page = require('page');
const misc = require('../util/misc.js');
const topNavController = require('../controllers/top_nav_controller.js');
const pageController = require('../controllers/page_controller.js');
const CommentsPageView = require('../views/comments_page_view.js');
const EmptyView = require('../views/empty_view.js');

class CommentsController {
    registerRoutes() {
        page('/comments/:query?',
            (ctx, next) => { misc.parseSearchQueryRoute(ctx, next); },
            (ctx, next) => { this._listCommentsRoute(ctx); });
        this._commentsPageView = new CommentsPageView();
        this._emptyView = new EmptyView();
    }

    _listCommentsRoute(ctx) {
        topNavController.activate('comments');

        pageController.run({
            searchQuery: ctx.searchQuery,
            clientUrl: '/comments/' + misc.formatSearchQuery({page: '{page}'}),
            requestPage: page => {
                return api.get(
                    '/posts/?query=sort:comment-date+comment-count-min:1' +
                    `&page=${page}&pageSize=10&fields=` +
                    'id,comments,commentCount,thumbnailUrl');
            },
            pageRenderer: this._commentsPageView,
            pageContext: {
                canViewPosts: api.hasPrivilege('posts:view'),
            }
        });
    }
}

module.exports = new CommentsController();
