"use strict";

const api = require("../api.js");
const uri = require("../util/uri.js");
const PostList = require("../models/post_list.js");
const topNavigation = require("../models/top_navigation.js");
const PageController = require("../controllers/page_controller.js");
const CommentsPageView = require("../views/comments_page_view.js");
const EmptyView = require("../views/empty_view.js");

const fields = ["id", "comments", "commentCount", "thumbnailUrl", "customThumbnailUrl"];

class CommentsController {
    constructor(ctx) {
        if (!api.hasPrivilege("comments:list")) {
            this._view = new EmptyView();
            this._view.showError(
                "You don't have privileges to view comments."
            );
            return;
        }

        topNavigation.activate("comments");
        topNavigation.setTitle("Listing comments");

        this._pageController = new PageController();
        this._pageController.run({
            parameters: ctx.parameters,
            defaultLimit: 10,
            getClientUrlForPage: (offset, limit) => {
                const parameters = Object.assign({}, ctx.parameters, {
                    offset: offset,
                    limit: limit,
                });
                return uri.formatClientLink("comments", parameters);
            },
            requestPage: (offset, limit) => {
                return PostList.search(
                    "sort:comment-date comment-count-min:1",
                    offset,
                    limit,
                    fields
                );
            },
            pageRenderer: (pageCtx) => {
                Object.assign(pageCtx, {
                    canViewPosts: api.hasPrivilege("posts:view"),
                });
                const view = new CommentsPageView(pageCtx);
                view.addEventListener("submit", (e) => this._evtUpdate(e));
                view.addEventListener("score", (e) => this._evtScore(e));
                view.addEventListener("delete", (e) => this._evtDelete(e));
                return view;
            },
        });
    }

    _evtUpdate(e) {
        // TODO: disable form
        e.detail.comment.text = e.detail.text;
        e.detail.comment.save().catch((error) => {
            e.detail.target.showError(error.message);
            // TODO: enable form
        });
    }

    _evtScore(e) {
        e.detail.comment
            .setScore(e.detail.score)
            .catch((error) => window.alert(error.message));
    }

    _evtDelete(e) {
        e.detail.comment
            .delete()
            .catch((error) => window.alert(error.message));
    }
}

module.exports = (router) => {
    router.enter(["comments"], (ctx, next) => {
        new CommentsController(ctx);
    });
};
