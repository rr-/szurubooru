'use strict';

const page = require('page');
const api = require('../api.js');
const misc = require('../util/misc.js');
const topNavController = require('../controllers/top_nav_controller.js');
const pageController = require('../controllers/page_controller.js');
const TagListHeaderView = require('../views/tag_list_header_view.js');
const TagListPageView = require('../views/tag_list_page_view.js');

class TagsController {
    constructor() {
        this.tagListHeaderView = new TagListHeaderView();
        this.tagListPageView = new TagListPageView();
    }

    registerRoutes() {
        page(
            '/tags/:query?',
            (ctx, next) => { misc.parseSearchQueryRoute(ctx, next); },
            (ctx, next) => { this.listTagsRoute(ctx, next); });
    }

    listTagsRoute(ctx, next) {
        topNavController.activate('tags');

        pageController.run({
            state: ctx.state,
            requestPage: page => {
                return api.get(
                    '/tags/?query={text}&page={page}&pageSize=50'.format({
                        text: ctx.searchQuery.text,
                        page: page}));
            },
            clientUrl: '/tags/' + misc.formatSearchQuery({
                text: ctx.searchQuery.text, page: '{page}'}),
            searchQuery: ctx.searchQuery,
            headerRenderer: this.tagListHeaderView,
            pageRenderer: this.tagListPageView,
        });
    }
}

module.exports = new TagsController();
