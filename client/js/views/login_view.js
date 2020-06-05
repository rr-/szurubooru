"use strict";

const events = require("../events.js");
const api = require("../api.js");
const views = require("../util/views.js");

const template = views.getTemplate("login");

class LoginView extends events.EventTarget {
    constructor() {
        super();
        this._hostNode = document.getElementById("content-holder");

        views.replaceContent(
            this._hostNode,
            template({
                userNamePattern: api.getUserNameRegex(),
                passwordPattern: api.getPasswordRegex(),
                canSendMails: api.canSendMails(),
            })
        );
        views.syncScrollPosition();

        views.decorateValidator(this._formNode);
        this._userNameInputNode.setAttribute(
            "pattern",
            api.getUserNameRegex()
        );
        this._passwordInputNode.setAttribute(
            "pattern",
            api.getPasswordRegex()
        );
        this._formNode.addEventListener("submit", (e) => {
            e.preventDefault();
            this.dispatchEvent(
                new CustomEvent("submit", {
                    detail: {
                        name: this._userNameInputNode.value,
                        password: this._passwordInputNode.value,
                        remember: this._rememberInputNode.checked,
                    },
                })
            );
        });
    }

    get _formNode() {
        return this._hostNode.querySelector("form");
    }

    get _userNameInputNode() {
        return this._formNode.querySelector("[name=name]");
    }

    get _passwordInputNode() {
        return this._formNode.querySelector("[name=password]");
    }

    get _rememberInputNode() {
        return this._formNode.querySelector("[name=remember-user]");
    }

    disableForm() {
        views.disableForm(this._formNode);
    }

    enableForm() {
        views.enableForm(this._formNode);
    }

    clearMessages() {
        views.clearMessages(this._hostNode);
    }

    showError(message) {
        views.showError(this._hostNode, message);
    }
}

module.exports = LoginView;
