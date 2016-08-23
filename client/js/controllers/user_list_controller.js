'use strict';

const api = require('../api.js');
const misc = require('../util/misc.js');
const UserList = require('../models/user_list.js');
const topNavigation = require('../models/top_navigation.js');
const PageController = require('../controllers/page_controller.js');
const UsersHeaderView = require('../views/users_header_view.js');
const UsersPageView = require('../views/users_page_view.js');
const EmptyView = require('../views/empty_view.js');

class UserListController {
    constructor(ctx) {
        if (!api.hasPrivilege('users:list')) {
            this._view = new EmptyView();
            this._view.showError('You don\'t have privileges to view users.');
            return;
        }

        topNavigation.activate('users');
        topNavigation.setTitle('Listing users');

        this._pageController = new PageController({
            parameters: ctx.parameters,
            getClientUrlForPage: page => {
                const parameters = Object.assign(
                    {}, ctx.parameters, {page: page});
                return '/users/' + misc.formatUrlParameters(parameters);
            },
            requestPage: page => {
                return UserList.search(ctx.parameters.query, page);
            },
            headerRenderer: headerCtx => {
                return new UsersHeaderView(headerCtx);
            },
            pageRenderer: pageCtx => {
                Object.assign(pageCtx, {
                    canViewUsers: api.hasPrivilege('users:view'),
                });
                return new UsersPageView(pageCtx);
            },
        });
    }

    showSuccess(message) {
        this._pageController.showSuccess(message);
    }
}

module.exports = router => {
    router.enter(
        '/users/:parameters?',
        (ctx, next) => { misc.parseUrlParametersRoute(ctx, next); },
        (ctx, next) => { ctx.controller = new UserListController(ctx); });
};
