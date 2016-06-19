'use strict';

const api = require('../api.js');
const misc = require('../util/misc.js');
const TagList = require('../models/tag_list.js');
const topNavigation = require('../models/top_navigation.js');
const PageController = require('../controllers/page_controller.js');
const TagsHeaderView = require('../views/tags_header_view.js');
const TagsPageView = require('../views/tags_page_view.js');

const fields = [
    'names', 'suggestions', 'implications', 'lastEditTime', 'usages'];

class TagListController {
    constructor(ctx) {
        topNavigation.activate('tags');

        this._pageController = new PageController({
            searchQuery: ctx.searchQuery,
            clientUrl: '/tags/' + misc.formatSearchQuery({
                text: ctx.searchQuery.text, page: '{page}'}),
            requestPage: page => {
                return TagList.search(ctx.searchQuery.text, page, 50, fields);
            },
            headerRenderer: headerCtx => {
                Object.assign(headerCtx, {
                    canEditTagCategories:
                        api.hasPrivilege('tagCategories:edit'),
                });
                return new TagsHeaderView(headerCtx);
            },
            pageRenderer: pageCtx => {
                return new TagsPageView(pageCtx);
            },
        });
    }

    showSuccess(message) {
        this._pageController.showSuccess(message);
    }

    showError(message) {
        this._pageController.showError(message);
    }
}

module.exports = router => {
    router.enter(
        '/tags/:query?',
        (ctx, next) => { misc.parseSearchQueryRoute(ctx, next); },
        (ctx, next) => { ctx.controller = new TagListController(ctx); });
};
