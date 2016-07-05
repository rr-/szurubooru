'use strict';

const api = require('../api.js');
const misc = require('../util/misc.js');
const UserList = require('../models/user_list.js');
const topNavigation = require('../models/top_navigation.js');
const PageController = require('../controllers/page_controller.js');
const UsersHeaderView = require('../views/users_header_view.js');
const UsersPageView = require('../views/users_page_view.js');

class UserListController {
    constructor(ctx) {
        topNavigation.activate('users');

        this._pageController = new PageController({
            searchQuery: ctx.searchQuery,
            getClientUrlForPage: page => {
                const searchQuery = Object.assign(
                    {}, ctx.searchQuery, {page: page});
                return '/users/' + misc.formatSearchQuery(searchQuery);
            },
            requestPage: page => {
                return UserList.search(ctx.searchQuery.text, page);
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
        '/users/:query?',
        (ctx, next) => { misc.parseSearchQueryRoute(ctx, next); },
        (ctx, next) => { ctx.controller = new UserListController(ctx); });
};
