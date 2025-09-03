"use strict";

const router = require("../router.js");
const api = require("../api.js");
const misc = require("../util/misc.js");
const uri = require("../util/uri.js");
const Pool = require("../models/pool.js");
const Post = require("../models/post.js");
const PoolCategoryList = require("../models/pool_category_list.js");
const topNavigation = require("../models/top_navigation.js");
const PoolView = require("../views/pool_view.js");
const EmptyView = require("../views/empty_view.js");

class PoolController {
    constructor(ctx, section) {
        if (!api.hasPrivilege("pools:view")) {
            this._view = new EmptyView();
            this._view.showError("You don't have privileges to view pools.");
            return;
        }

        Promise.all([
            PoolCategoryList.get(),
            Pool.get(ctx.parameters.id),
        ]).then(
            (responses) => {
                const [poolCategoriesResponse, pool] = responses;

                topNavigation.activate("pools");
                topNavigation.setTitle("Pool #" + pool.names[0]);

                this._name = ctx.parameters.name;
                pool.addEventListener("change", (e) =>
                    this._evtSaved(e, section)
                );

                const categories = {};
                for (let category of poolCategoriesResponse.results) {
                    categories[category.name] = category.name;
                }

                this._view = new PoolView({
                    pool: pool,
                    section: section,
                    canEditAnything: api.hasPrivilege("pools:edit"),
                    canEditNames: api.hasPrivilege("pools:edit:names"),
                    canEditCategory: api.hasPrivilege("pools:edit:category"),
                    canEditDescription: api.hasPrivilege(
                        "pools:edit:description"
                    ),
                    canEditPosts: api.hasPrivilege("pools:edit:posts"),
                    canMerge: api.hasPrivilege("pools:merge"),
                    canDelete: api.hasPrivilege("pools:delete"),
                    categories: categories,
                    escapeTagName: uri.escapeTagName,
                });

                this._view.addEventListener("change", (e) =>
                    this._evtChange(e)
                );
                this._view.addEventListener("submit", (e) =>
                    this._evtUpdate(e)
                );
                this._view.addEventListener("merge", (e) => this._evtMerge(e));
                this._view.addEventListener("delete", (e) =>
                    this._evtDelete(e)
                );
            },
            (error) => {
                this._view = new EmptyView();
                this._view.showError(error.message);
            }
        );
    }

    _evtChange(e) {
        misc.enableExitConfirmation();
    }

    _evtSaved(e, section) {
        misc.disableExitConfirmation();
        if (this._name !== e.detail.pool.names[0]) {
            router.replace(
                uri.formatClientLink("pool", e.detail.pool.id, section),
                null,
                false
            );
        }
    }

    _evtUpdate(e) {
        this._view.clearMessages();
        this._view.disableForm();
        if (e.detail.names !== undefined && e.detail.names !== null) {
            e.detail.pool.names = e.detail.names;
        }
        if (e.detail.category !== undefined && e.detail.category !== null) {
            e.detail.pool.category = e.detail.category;
        }
        if (e.detail.description !== undefined && e.detail.description !== null) {
            e.detail.pool.description = e.detail.description;
        }
        if (e.detail.posts !== undefined && e.detail.posts !== null) {
            e.detail.pool.posts.clear();
            for (let postId of e.detail.posts) {
                e.detail.pool.posts.add(
                    Post.fromResponse({ id: parseInt(postId) })
                );
            }
        }
        e.detail.pool.save().then(
            () => {
                this._view.showSuccess("Pool saved.");
                this._view.enableForm();
            },
            (error) => {
                this._view.showError(error.message);
                this._view.enableForm();
            }
        );
    }

    _evtMerge(e) {
        this._view.clearMessages();
        this._view.disableForm();
        e.detail.pool.merge(e.detail.targetPoolId, e.detail.addAlias).then(
            () => {
                this._view.showSuccess("Pool merged.");
                this._view.enableForm();
                router.replace(
                    uri.formatClientLink(
                        "pool",
                        e.detail.targetPoolId,
                        "merge"
                    ),
                    null,
                    false
                );
            },
            (error) => {
                this._view.showError(error.message);
                this._view.enableForm();
            }
        );
    }

    _evtDelete(e) {
        this._view.clearMessages();
        this._view.disableForm();
        e.detail.pool.delete().then(
            () => {
                const ctx = router.show(uri.formatClientLink("pools"));
                ctx.controller.showSuccess("Pool deleted.");
            },
            (error) => {
                this._view.showError(error.message);
                this._view.enableForm();
            }
        );
    }
}

module.exports = (router) => {
    router.enter(["pool", ":id", "edit"], (ctx, next) => {
        ctx.controller = new PoolController(ctx, "edit");
    });
    router.enter(["pool", ":id", "merge"], (ctx, next) => {
        ctx.controller = new PoolController(ctx, "merge");
    });
    router.enter(["pool", ":id", "delete"], (ctx, next) => {
        ctx.controller = new PoolController(ctx, "delete");
    });
    router.enter(["pool", ":id"], (ctx, next) => {
        ctx.controller = new PoolController(ctx, "summary");
    });
};
