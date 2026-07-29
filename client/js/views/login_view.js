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
                oidcEnabled: api.oidcEnabled(),
                oidcButtonLabel: api.oidcButtonLabel(),
                oidcButtonIcon: api.oidcButtonIcon(),
                disablePasswordAuth: api.disablePasswordAuth(),
            })
        );
        views.syncScrollPosition();

        if (this._formNode) {
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
        if (this._oidcBtnNode) {
            this._oidcBtnNode.addEventListener("click", () => {
                this.dispatchEvent(new CustomEvent("oidcLogin"));
            });
        }
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

    get _oidcBtnNode() {
        return this._hostNode.querySelector(".oidc-login-btn");
    }

    disableForm() {
        if (this._formNode) {
            views.disableForm(this._formNode);
        }
    }

    enableForm() {
        if (this._formNode) {
            views.enableForm(this._formNode);
        }
    }

    clearMessages() {
        views.clearMessages(this._hostNode);
    }

    showError(message) {
        views.showError(this._hostNode, message);
    }
}

module.exports = LoginView;
