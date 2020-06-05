"use strict";

const events = require("../events.js");
const views = require("../util/views.js");
const UserDeleteView = require("./user_delete_view.js");
const UserTokensView = require("./user_tokens_view.js");
const UserSummaryView = require("./user_summary_view.js");
const UserEditView = require("./user_edit_view.js");
const EmptyView = require("../views/empty_view.js");

const template = views.getTemplate("user");

class UserView extends events.EventTarget {
    constructor(ctx) {
        super();

        this._ctx = ctx;
        ctx.user.addEventListener("change", (e) => this._evtChange(e));
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

        ctx.hostNode = this._hostNode.querySelector("#user-content-holder");
        if (ctx.section === "edit") {
            if (!this._ctx.canEditAnything) {
                this._view = new EmptyView();
                this._view.showError(
                    "You don't have privileges to edit users."
                );
            } else {
                this._view = new UserEditView(ctx);
                events.proxyEvent(this._view, this, "submit");
            }
        } else if (ctx.section === "list-tokens") {
            if (!this._ctx.canListTokens) {
                this._view = new EmptyView();
                this._view.showError(
                    "You don't have privileges to view user tokens."
                );
            } else {
                this._view = new UserTokensView(ctx);
                events.proxyEvent(this._view, this, "delete", "delete-token");
                events.proxyEvent(this._view, this, "submit", "create-token");
                events.proxyEvent(this._view, this, "update", "update-token");
            }
        } else if (ctx.section === "delete") {
            if (!this._ctx.canDelete) {
                this._view = new EmptyView();
                this._view.showError(
                    "You don't have privileges to delete users."
                );
            } else {
                this._view = new UserDeleteView(ctx);
                events.proxyEvent(this._view, this, "submit", "delete");
            }
        } else {
            this._view = new UserSummaryView(ctx);
        }

        events.proxyEvent(this._view, this, "change");
        views.syncScrollPosition();
    }

    clearMessages() {
        this._view.clearMessages();
    }

    showSuccess(message) {
        this._view.showSuccess(message);
    }

    showError(message) {
        this._view.showError(message);
    }

    enableForm() {
        this._view.enableForm();
    }

    disableForm() {
        this._view.disableForm();
    }

    _evtChange(e) {
        this._ctx.user = e.detail.user;
        this._install(this._ctx);
    }
}

module.exports = UserView;
