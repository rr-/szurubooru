'use strict';

const api = require('../api.js');
const misc = require('../util/misc.js');
const SnapshotList = require('../models/snapshot_list.js');
const PageController = require('../controllers/page_controller.js');
const topNavigation = require('../models/top_navigation.js');
const SnapshotsPageView = require('../views/snapshots_page_view.js');

class SnapshotsController {
    constructor(ctx) {
        topNavigation.activate('');
        topNavigation.setTitle('History');

        this._pageController = new PageController({
            parameters: ctx.parameters,
            getClientUrlForPage: page => {
                const parameters = Object.assign(
                    {}, ctx.parameters, {page: page});
                return '/history/' + misc.formatUrlParameters(parameters);
            },
            requestPage: page => {
                return SnapshotList.search('', page, 25);
            },
            pageRenderer: pageCtx => {
                Object.assign(pageCtx, {
                    canViewPosts: api.hasPrivilege('posts:view'),
                    canViewUsers: api.hasPrivilege('users:view'),
                    canViewTags: api.hasPrivilege('tags:view'),
                });
                return new SnapshotsPageView(pageCtx);
            },
        });
    }
}

module.exports = router => {
    router.enter('/history/:parameters?',
        (ctx, next) => { misc.parseUrlParametersRoute(ctx, next); },
        (ctx, next) => { ctx.controller = new SnapshotsController(ctx); });
};
