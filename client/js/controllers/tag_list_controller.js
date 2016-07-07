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
            parameters: ctx.parameters,
            getClientUrlForPage: page => {
                const parameters = Object.assign(
                    {}, ctx.parameters, {page: page});
                return '/tags/' + misc.formatUrlParameters(parameters);
            },
            requestPage: page => {
                return TagList.search(ctx.parameters.query, page, 50, fields);
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
        '/tags/:parameters?',
        (ctx, next) => { misc.parseUrlParametersRoute(ctx, next); },
        (ctx, next) => { ctx.controller = new TagListController(ctx); });
};
