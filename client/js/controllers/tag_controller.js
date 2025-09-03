"use strict";

const router = require("../router.js");
const api = require("../api.js");
const misc = require("../util/misc.js");
const uri = require("../util/uri.js");
const Tag = require("../models/tag.js");
const TagCategoryList = require("../models/tag_category_list.js");
const topNavigation = require("../models/top_navigation.js");
const TagView = require("../views/tag_view.js");
const EmptyView = require("../views/empty_view.js");

class TagController {
    constructor(ctx, section) {
        if (!api.hasPrivilege("tags:view")) {
            this._view = new EmptyView();
            this._view.showError("You don't have privileges to view tags.");
            return;
        }

        Promise.all([
            TagCategoryList.get(),
            Tag.get(ctx.parameters.name),
        ]).then(
            (responses) => {
                const [tagCategoriesResponse, tag] = responses;

                topNavigation.activate("tags");
                topNavigation.setTitle("Tag #" + tag.names[0]);

                this._name = ctx.parameters.name;
                tag.addEventListener("change", (e) =>
                    this._evtSaved(e, section)
                );

                const categories = {};
                for (let category of tagCategoriesResponse.results) {
                    categories[category.name] = category.name;
                }

                this._view = new TagView({
                    tag: tag,
                    section: section,
                    canEditAnything: api.hasPrivilege("tags:edit"),
                    canEditNames: api.hasPrivilege("tags:edit:names"),
                    canEditCategory: api.hasPrivilege("tags:edit:category"),
                    canEditImplications: api.hasPrivilege(
                        "tags:edit:implications"
                    ),
                    canEditSuggestions: api.hasPrivilege(
                        "tags:edit:suggestions"
                    ),
                    canEditDescription: api.hasPrivilege(
                        "tags:edit:description"
                    ),
                    canMerge: api.hasPrivilege("tags:merge"),
                    canDelete: api.hasPrivilege("tags:delete"),
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
        if (this._name !== e.detail.tag.names[0]) {
            router.replace(
                uri.formatClientLink("tag", e.detail.tag.names[0], section),
                null,
                false
            );
        }
    }

    _evtUpdate(e) {
        this._view.clearMessages();
        this._view.disableForm();
        if (e.detail.names !== undefined && e.detail.names !== null) {
            e.detail.tag.names = e.detail.names;
        }
        if (e.detail.category !== undefined && e.detail.category !== null) {
            e.detail.tag.category = e.detail.category;
        }
        if (e.detail.description !== undefined && e.detail.description !== null) {
            e.detail.tag.description = e.detail.description;
        }
        e.detail.tag.save().then(
            () => {
                this._view.showSuccess("Tag saved.");
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
        e.detail.tag.merge(e.detail.targetTagName, e.detail.addAlias).then(
            () => {
                this._view.showSuccess("Tag merged.");
                this._view.enableForm();
                router.replace(
                    uri.formatClientLink(
                        "tag",
                        e.detail.targetTagName,
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
        e.detail.tag.delete().then(
            () => {
                const ctx = router.show(uri.formatClientLink("tags"));
                ctx.controller.showSuccess("Tag deleted.");
            },
            (error) => {
                this._view.showError(error.message);
                this._view.enableForm();
            }
        );
    }
}

module.exports = (router) => {
    router.enter(["tag", ":name", "edit"], (ctx, next) => {
        ctx.controller = new TagController(ctx, "edit");
    });
    router.enter(["tag", ":name", "merge"], (ctx, next) => {
        ctx.controller = new TagController(ctx, "merge");
    });
    router.enter(["tag", ":name", "delete"], (ctx, next) => {
        ctx.controller = new TagController(ctx, "delete");
    });
    router.enter(["tag", ":name"], (ctx, next) => {
        ctx.controller = new TagController(ctx, "summary");
    });
};
