"use strict";

const router = require("../router.js");
const api = require("../api.js");
const uri = require("../util/uri.js");
const TagList = require("../models/tag_list.js");
const topNavigation = require("../models/top_navigation.js");
const PageController = require("../controllers/page_controller.js");
const TagsHeaderView = require("../views/tags_header_view.js");
const TagsPageView = require("../views/tags_page_view.js");
const EmptyView = require("../views/empty_view.js");

const fields = [
    "names",
    "suggestions",
    "implications",
    "creationTime",
    "usages",
    "category",
];

class TagListController {
    constructor(ctx) {
        this._pageController = new PageController();

        if (!api.hasPrivilege("tags:list")) {
            this._view = new EmptyView();
            this._view.showError("You don't have privileges to view tags.");
            return;
        }

        this._ctx = ctx;

        topNavigation.activate("tags");
        topNavigation.setTitle("Listing tags");

        this._headerView = new TagsHeaderView({
            hostNode: this._pageController.view.pageHeaderHolderNode,
            parameters: ctx.parameters,
            canEditTagCategories: api.hasPrivilege("tagCategories:edit"),
        });
        this._headerView.addEventListener("navigate", (e) =>
            this._evtNavigate(e)
        );

        this._syncPageController();
    }

    showSuccess(message) {
        this._pageController.showSuccess(message);
    }

    showError(message) {
        this._pageController.showError(message);
    }

    _evtNavigate(e) {
        router.showNoDispatch(
            uri.formatClientLink("tags", e.detail.parameters)
        );
        Object.assign(this._ctx.parameters, e.detail.parameters);
        this._syncPageController();
    }

    _syncPageController() {
        this._pageController.run({
            parameters: this._ctx.parameters,
            defaultLimit: 50,
            getClientUrlForPage: (offset, limit) => {
                const parameters = Object.assign({}, this._ctx.parameters, {
                    offset: offset,
                    limit: limit,
                });
                return uri.formatClientLink("tags", parameters);
            },
            requestPage: (offset, limit) => {
                return TagList.search(
                    this._ctx.parameters.query,
                    offset,
                    limit,
                    fields,
                    {}
                );
            },
            pageRenderer: (pageCtx) => {
                return new TagsPageView(pageCtx);
            },
        });
    }
}

module.exports = (router) => {
    router.enter(["tags"], (ctx, next) => {
        ctx.controller = new TagListController(ctx);
    });
};
