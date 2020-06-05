"use strict";

const events = require("../events.js");
const api = require("../api.js");
const views = require("../util/views.js");

const template = views.getTemplate("password-reset");

class PasswordResetView extends events.EventTarget {
    constructor() {
        super();
        this._hostNode = document.getElementById("content-holder");

        views.replaceContent(
            this._hostNode,
            template({
                canSendMails: api.canSendMails(),
                contactEmail: api.getContactEmail(),
            })
        );
        views.syncScrollPosition();

        views.decorateValidator(this._formNode);
        this._formNode.addEventListener("submit", (e) => {
            e.preventDefault();
            this.dispatchEvent(
                new CustomEvent("submit", {
                    detail: {
                        userNameOrEmail: this._userNameOrEmailFieldNode.value,
                    },
                })
            );
        });
    }

    showSuccess(message) {
        views.showSuccess(this._hostNode, message);
    }

    showError(message) {
        views.showError(this._hostNode, message);
    }

    clearMessages() {
        views.clearMessages(this._hostNode);
    }

    enableForm() {
        views.enableForm(this._formNode);
    }

    disableForm() {
        views.disableForm(this._formNode);
    }

    get _formNode() {
        return this._hostNode.querySelector("form");
    }

    get _userNameOrEmailFieldNode() {
        return this._formNode.querySelector("[name=user-name]");
    }
}

module.exports = PasswordResetView;
