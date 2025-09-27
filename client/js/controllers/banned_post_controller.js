"use strict";

const api = require("../api.js");
const BannedPostList = require("../models/banned_post_list.js");
const topNavigation = require("../models/top_navigation.js");
const BannedPostsView = require("../views/banned_posts_view.js");
const EmptyView = require("../views/empty_view.js");

class BannedPostController {
    constructor() {
        if (!api.hasPrivilege("posts:ban:list")) {
            this._view = new EmptyView();
            this._view.showError(
                "You don't have privileges to view banned posts."
            );
            return;
        }

        topNavigation.activate("banned-posts");
        topNavigation.setTitle("Listing banned posts");
        BannedPostList.get().then(
            (response) => {
                this._bannedPosts = response.results;
                this._view = new BannedPostsView({
                    bannedPosts: this._bannedPosts,
                    canDelete: api.hasPrivilege("poolCategories:delete")
                });
                this._view.addEventListener("submit", (e) =>
                    this._evtSubmit(e)
                );
            },
            (error) => {
                this._view = new EmptyView();
                this._view.showError(error.message);
            }
        );
    }

    _evtSubmit(e) {
        this._view.clearMessages();
        this._view.disableForm();
        this._bannedPosts.save().then(
            () => {
                this._view.enableForm();
                this._view.showSuccess("Changes saved.");
            },
            (error) => {
                this._view.enableForm();
                this._view.showError(error.message);
            }
        );
    }
}

module.exports = (router) => {
    router.enter(["banned-posts"], (ctx, next) => {
        ctx.controller = new BannedPostController(ctx, next);
    });
};
