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

        this._ctx = ctx;
        this._pageController = new PageController();

        this._headerView = new UsersHeaderView({
            hostNode: this._pageController.view.pageHeaderHolderNode,
            parameters: ctx.parameters,
        });
        this._headerView.addEventListener(
            'navigate', e => this._evtNavigate(e));

        this._syncPageController();
    }

    showSuccess(message) {
        this._pageController.showSuccess(message);
    }

    _evtNavigate(e) {
        history.pushState(
            null,
            window.title,
            '/users/' + misc.formatUrlParameters(e.detail.parameters));
        Object.assign(this._ctx.parameters, e.detail.parameters);
        this._syncPageController();
    }

    _syncPageController() {
        this._pageController.run({
            parameters: this._ctx.parameters,
            getClientUrlForPage: page => {
                const parameters = Object.assign(
                    {}, this._ctx.parameters, {page: page});
                return '/users/' + misc.formatUrlParameters(parameters);
            },
            requestPage: page => {
                return UserList.search(this._ctx.parameters.query, page);
            },
            pageRenderer: pageCtx => {
                Object.assign(pageCtx, {
                    canViewUsers: api.hasPrivilege('users:view'),
                });
                return new UsersPageView(pageCtx);
            },
        });
    }
}

module.exports = router => {
    router.enter(
        '/users/:parameters(.*)?',
        (ctx, next) => { misc.parseUrlParametersRoute(ctx, next); },
        (ctx, next) => { ctx.controller = new UserListController(ctx); });
};
