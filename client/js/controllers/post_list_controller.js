'use strict';

const api = require('../api.js');
const settings = require('../models/settings.js');
const misc = require('../util/misc.js');
const PostList = require('../models/post_list.js');
const topNavigation = require('../models/top_navigation.js');
const PageController = require('../controllers/page_controller.js');
const PostsHeaderView = require('../views/posts_header_view.js');
const PostsPageView = require('../views/posts_page_view.js');

const fields = [
    'id', 'thumbnailUrl', 'type',
    'score', 'favoriteCount', 'commentCount', 'tags'];

class PostListController {
    constructor(ctx) {
        topNavigation.activate('posts');

        this._pageController = new PageController({
            searchQuery: ctx.searchQuery,
            clientUrl: '/posts/' + misc.formatSearchQuery({
                text: ctx.searchQuery.text, page: '{page}'}),
            requestPage: page => {
                return PostList.search(
                    this._decorateSearchQuery(ctx.searchQuery.text),
                    page, 40, fields);
            },
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
