"use strict";

const events = require("../events.js");
const views = require("../util/views.js");
const BannedPost = require("../models/banned_post.js");

const template = views.getTemplate("banned-post-list");
const rowTemplate = views.getTemplate("banned-post-entry");

class BannedPostsView extends events.EventTarget {
    constructor(ctx) {
        super();
        this._ctx = ctx;
        this._hostNode = document.getElementById("content-holder");

        views.replaceContent(this._hostNode, template(ctx));
        views.syncScrollPosition();
        views.decorateValidator(this._formNode);

        const bannedPostsToAdd = Array.from(ctx.bannedPosts);
        for (let bannedPost of bannedPostsToAdd) {
            this._addBannedPostRowNode(bannedPost);
        }

        ctx.bannedPosts.addEventListener("remove", (e) =>
            this._evtBannedPostDeleted(e)
        );

        this._formNode.addEventListener("submit", (e) =>
            this._evtSaveButtonClick(e, ctx)
        );
    }

    enableForm() {
        views.enableForm(this._formNode);
    }

    disableForm() {
        views.disableForm(this._formNode);
    }

    clearMessages() {
        views.clearMessages(this._hostNode);
    }

    showSuccess(message) {
        views.showSuccess(this._hostNode, message);
    }

    showError(message) {
        views.showError(this._hostNode, message);
    }

    get _formNode() {
        return this._hostNode.querySelector("form");
    }

    get _tableBodyNode() {
        return this._hostNode.querySelector("tbody");
    }

    _addBannedPostRowNode(bannedPost) {
        const rowNode = rowTemplate(
            Object.assign({}, this._ctx, { postBan: bannedPost })
        );

        const removeLinkNode = rowNode.querySelector(".remove a");
        if (removeLinkNode) {
            removeLinkNode.addEventListener("click", (e) =>
                this._evtDeleteButtonClick(e, rowNode)
            );
        }

        this._tableBodyNode.appendChild(rowNode);

        rowNode._bannedPost = bannedPost;
        bannedPost._rowNode = rowNode;
    }

    _removeBannedPostRowNode(bannedPost) {
        const rowNode = bannedPost._rowNode;
        rowNode.parentNode.removeChild(rowNode);
    }

    _evtBannedPostDeleted(e) {
        this._removeBannedPostRowNode(e.detail.bannedPost);
    }

    _evtDeleteButtonClick(e, rowNode, link) {
        e.preventDefault();
        if (e.target.classList.contains("inactive")) {
            return;
        }
        this._ctx.bannedPosts.remove(rowNode._bannedPost);
    }

    _evtSaveButtonClick(e, ctx) {
        e.preventDefault();
        this.dispatchEvent(new CustomEvent("submit"));
    }
}

module.exports = BannedPostsView;
