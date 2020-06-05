"use strict";

const events = require("../events.js");
const views = require("../util/views.js");

const template = views.getTemplate("user-delete");

class UserDeleteView extends events.EventTarget {
    constructor(ctx) {
        super();

        this._user = ctx.user;
        this._hostNode = ctx.hostNode;
        views.replaceContent(this._hostNode, template(ctx));
        views.decorateValidator(this._formNode);

        this._formNode.addEventListener("submit", (e) => this._evtSubmit(e));
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

    enableForm() {
        views.enableForm(this._formNode);
    }

    disableForm() {
        views.disableForm(this._formNode);
    }

    _evtSubmit(e) {
        e.preventDefault();
        this.dispatchEvent(
            new CustomEvent("submit", {
                detail: {
                    user: this._user,
                },
            })
        );
    }

    get _formNode() {
        return this._hostNode.querySelector("form");
    }
}

module.exports = UserDeleteView;
