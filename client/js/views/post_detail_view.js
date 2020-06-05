"use strict";

const events = require("../events.js");
const views = require("../util/views.js");
const PostMergeView = require("./post_merge_view.js");
const EmptyView = require("../views/empty_view.js");

const template = views.getTemplate("post-detail");

class PostDetailView extends events.EventTarget {
    constructor(ctx) {
        super();

        this._ctx = ctx;
        ctx.post.addEventListener("change", (e) => this._evtChange(e));
        ctx.section = ctx.section || "summary";

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

        ctx.hostNode = this._hostNode.querySelector(".post-content-holder");
        if (ctx.section === "merge") {
            if (!this._ctx.canMerge) {
                this._view = new EmptyView();
                this._view.showError(
                    "You don't have privileges to merge posts."
                );
            } else {
                this._view = new PostMergeView(ctx);
                events.proxyEvent(this._view, this, "select");
                events.proxyEvent(this._view, this, "submit", "merge");
            }
        } else {
            // this._view = new PostSummaryView(ctx);
        }

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

    selectPost(post) {
        this._view.selectPost(post);
    }

    _evtChange(e) {
        this._ctx.post = e.detail.post;
        this._install(this._ctx);
    }
}

module.exports = PostDetailView;
