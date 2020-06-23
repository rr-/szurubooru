"use strict";

const events = require("../events.js");
const views = require("../util/views.js");
const misc = require("../util/misc.js");
const PoolSummaryView = require("./pool_summary_view.js");
const PoolEditView = require("./pool_edit_view.js");
const PoolMergeView = require("./pool_merge_view.js");
const PoolDeleteView = require("./pool_delete_view.js");
const EmptyView = require("../views/empty_view.js");

const template = views.getTemplate("pool");

class PoolView extends events.EventTarget {
    constructor(ctx) {
        super();

        this._ctx = ctx;
        ctx.pool.addEventListener("change", (e) => this._evtChange(e));
        ctx.section = ctx.section || "summary";
        ctx.getPrettyName = misc.getPrettyName;

        this._hostNode = document.getElementById("content-holder");
        this._install();
    }

    _install() {
        const ctx = this._ctx;
        views.replaceContent(this._hostNode, template(ctx));

        for (let item of this._hostNode.querySelectorAll("[data-name]")) {
            item.classList.toggle(
                "active",
                item.getAttribute("data-name") === ctx.section
            );
            if (item.getAttribute("data-name") === ctx.section) {
                item.parentNode.scrollLeft =
                    item.getBoundingClientRect().left -
                    item.parentNode.getBoundingClientRect().left;
            }
        }

        ctx.hostNode = this._hostNode.querySelector(".pool-content-holder");
        if (ctx.section === "edit") {
            if (!this._ctx.canEditAnything) {
                this._view = new EmptyView();
                this._view.showError(
                    "You don't have privileges to edit pools."
                );
            } else {
                this._view = new PoolEditView(ctx);
                events.proxyEvent(this._view, this, "submit");
            }
        } else if (ctx.section === "merge") {
            if (!this._ctx.canMerge) {
                this._view = new EmptyView();
                this._view.showError(
                    "You don't have privileges to merge pools."
                );
            } else {
                this._view = new PoolMergeView(ctx);
                events.proxyEvent(this._view, this, "submit", "merge");
            }
        } else if (ctx.section === "delete") {
            if (!this._ctx.canDelete) {
                this._view = new EmptyView();
                this._view.showError(
                    "You don't have privileges to delete pools."
                );
            } else {
                this._view = new PoolDeleteView(ctx);
                events.proxyEvent(this._view, this, "submit", "delete");
            }
        } else {
            this._view = new PoolSummaryView(ctx);
        }

        events.proxyEvent(this._view, this, "change");
        views.syncScrollPosition();
    }

    clearMessages() {
        this._view.clearMessages();
    }

    enableForm() {
        this._view.enableForm();
    }

    disableForm() {
        this._view.disableForm();
    }

    showSuccess(message) {
        this._view.showSuccess(message);
    }

    showError(message) {
        this._view.showError(message);
    }

    _evtChange(e) {
        this._ctx.pool = e.detail.pool;
        this._install(this._ctx);
    }
}

module.exports = PoolView;
