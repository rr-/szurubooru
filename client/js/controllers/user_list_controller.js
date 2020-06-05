"use strict";

const api = require("../api.js");
const router = require("../router.js");
const uri = require("../util/uri.js");
const UserList = require("../models/user_list.js");
const topNavigation = require("../models/top_navigation.js");
const PageController = require("../controllers/page_controller.js");
const UsersHeaderView = require("../views/users_header_view.js");
const UsersPageView = require("../views/users_page_view.js");
const EmptyView = require("../views/empty_view.js");

class UserListController {
    constructor(ctx) {
        this._pageController = new PageController();

        if (!api.hasPrivilege("users:list")) {
            this._view = new EmptyView();
            this._view.showError("You don't have privileges to view users.");
            return;
        }

        topNavigation.activate("users");
        topNavigation.setTitle("Listing users");

        this._ctx = ctx;

        this._headerView = new UsersHeaderView({
            hostNode: this._pageController.view.pageHeaderHolderNode,
            parameters: ctx.parameters,
        });
        this._headerView.addEventListener("navigate", (e) =>
            this._evtNavigate(e)
        );

        this._syncPageController();
    }

    showSuccess(message) {
        this._pageController.showSuccess(message);
    }

    _evtNavigate(e) {
        router.showNoDispatch(
            uri.formatClientLink("users", e.detail.parameters)
        );
        Object.assign(this._ctx.parameters, e.detail.parameters);
        this._syncPageController();
    }

    _syncPageController() {
        this._pageController.run({
            parameters: this._ctx.parameters,
            defaultLimit: 30,
            getClientUrlForPage: (offset, limit) => {
                const parameters = Object.assign({}, this._ctx.parameters, {
                    offset: offset,
                    limit: limit,
                });
                return uri.formatClientLink("users", parameters);
            },
            requestPage: (offset, limit) => {
                return UserList.search(
                    this._ctx.parameters.query,
                    offset,
                    limit
                );
            },
            pageRenderer: (pageCtx) => {
                Object.assign(pageCtx, {
                    canViewUsers: api.hasPrivilege("users:view"),
                });
                return new UsersPageView(pageCtx);
            },
        });
    }
}

module.exports = (router) => {
    router.enter(["users"], (ctx, next) => {
        ctx.controller = new UserListController(ctx);
    });
};
