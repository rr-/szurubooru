"use strict";

const api = require("../api.js");
const uri = require("../util/uri.js");
const SnapshotList = require("../models/snapshot_list.js");
const PageController = require("../controllers/page_controller.js");
const topNavigation = require("../models/top_navigation.js");
const SnapshotsPageView = require("../views/snapshots_page_view.js");
const EmptyView = require("../views/empty_view.js");

class SnapshotsController {
    constructor(ctx) {
        if (!api.hasPrivilege("snapshots:list")) {
            this._view = new EmptyView();
            this._view.showError("You don't have privileges to view history.");
            return;
        }

        topNavigation.activate("");
        topNavigation.setTitle("History");

        this._pageController = new PageController();
        this._pageController.run({
            parameters: ctx.parameters,
            defaultLimit: 25,
            getClientUrlForPage: (offset, limit) => {
                const parameters = Object.assign({}, ctx.parameters, {
                    offset: offset,
                    limit: limit,
                });
                return uri.formatClientLink("history", parameters);
            },
            requestPage: (offset, limit) => {
                return SnapshotList.search("", offset, limit);
            },
            pageRenderer: (pageCtx) => {
                Object.assign(pageCtx, {
                    canViewPosts: api.hasPrivilege("posts:view"),
                    canViewUsers: api.hasPrivilege("users:view"),
                    canViewTags: api.hasPrivilege("tags:view"),
                });
                return new SnapshotsPageView(pageCtx);
            },
        });
    }
}

module.exports = (router) => {
    router.enter(["history"], (ctx, next) => {
        ctx.controller = new SnapshotsController(ctx);
    });
};
