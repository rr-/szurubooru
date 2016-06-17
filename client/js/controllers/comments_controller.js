'use strict';

const api = require('../api.js');
const misc = require('../util/misc.js');
const PostList = require('../models/post_list.js');
const topNavigation = require('../models/top_navigation.js');
const PageController = require('../controllers/page_controller.js');
const CommentsPageView = require('../views/comments_page_view.js');

class CommentsController {
    constructor(ctx) {
        topNavigation.activate('comments');

        const proxy = PageController.createHistoryCacheProxy(
            ctx, page => {
                const url =
                    '/posts/?query=sort:comment-date+comment-count-min:1' +
                    `&page=${page}&pageSize=10&fields=` +
                    'id,comments,commentCount,thumbnailUrl';
                return api.get(url);
            });

        this._pageController = new PageController({
            searchQuery: ctx.searchQuery,
            clientUrl: '/comments/' + misc.formatSearchQuery({page: '{page}'}),
            requestPage: page => {
                return proxy(page).then(response => {
                    return Promise.resolve(Object.assign(
                        {},
                        response,
                        {results: PostList.fromResponse(response.results)}));
                });
            },
            pageRenderer: pageCtx => {
                Object.assign(pageCtx, {
                    canViewPosts: api.hasPrivilege('posts:view'),
                });
                const view = new CommentsPageView(pageCtx);
                view.addEventListener('change', e => this._evtChange(e));
                view.addEventListener('score', e => this._evtScore(e));
                view.addEventListener('delete', e => this._evtDelete(e));
                return view;
            },
        });
    }

    _evtChange(e) {
        // TODO: disable form
        e.detail.comment.text = e.detail.text;
        e.detail.comment.save()
            .catch(errorMessage => {
                e.detail.target.showError(errorMessage);
                // TODO: enable form
            });
    }

    _evtScore(e) {
        e.detail.comment.setScore(e.detail.score)
            .catch(errorMessage => {
                window.alert(errorMessage);
            });
    }

    _evtDelete(e) {
        e.detail.comment.delete()
            .catch(errorMessage => {
                window.alert(errorMessage);
            });
    }
};

module.exports = router => {
    router.enter('/comments/:query?',
        (ctx, next) => { misc.parseSearchQueryRoute(ctx, next); },
        (ctx, next) => { new CommentsController(ctx); });
};
