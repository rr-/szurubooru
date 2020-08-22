"use strict";

const events = require("../events.js");
const api = require("../api.js");
const views = require("../util/views.js");

const template = views.getTemplate("user-registration");

class RegistrationView extends events.EventTarget {
    constructor() {
        super();
        this._hostNode = document.getElementById("content-holder");
        views.replaceContent(
            this._hostNode,
            template({
                userNamePattern: api.getUserNameRegex(),
                passwordPattern: api.getPasswordRegex(),
                enableRecaptcha: api.recaptchaEnabled(),
            })
        );
        views.syncScrollPosition();
        views.decorateValidator(this._formNode);
        this._formNode.addEventListener("submit", (e) => this._evtSubmit(e));
        this.setRecaptchaToken = this.setRecaptchaToken.bind(this);

        if (api.recaptchaEnabled())
            this.renderRecaptcha();
    }

    renderRecaptcha() {
        grecaptcha.render(this._recaptchaNode, {
            "callback": this.setRecaptchaToken,
            "sitekey": api.getRecaptchaSiteKey(),
        });
    }

    setRecaptchaToken(token) {
        this.recaptchaToken = token;
    }

    clearMessages() {
        views.clearMessages(this._hostNode);
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
                    name: this._userNameFieldNode.value,
                    password: this._passwordFieldNode.value,
                    email: this._emailFieldNode.value,
                    recaptchaToken: this.recaptchaToken,
                },
            })
        );
    }

    get _formNode() {
        return this._hostNode.querySelector("form");
    }

    get _userNameFieldNode() {
        return this._formNode.querySelector("[name=name]");
    }

    get _passwordFieldNode() {
        return this._formNode.querySelector("[name=password]");
    }

    get _emailFieldNode() {
        return this._formNode.querySelector("[name=email]");
    }

    get _recaptchaNode() {
        return this._formNode.querySelector("#recaptcha");
    }
}

module.exports = RegistrationView;
