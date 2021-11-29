"use strict";

const events = require("../events.js");
const views = require("../util/views.js");

const template = views.getTemplate("user-tokens");

class UserTokenView extends events.EventTarget {
    constructor(ctx) {
        super();

        this._user = ctx.user;
        this._tokens = ctx.tokens;
        this._hostNode = ctx.hostNode;
        this._tokenFormNodes = [];
        views.replaceContent(this._hostNode, template(ctx));
        views.decorateValidator(this._formNode);

        this._formNode.addEventListener("submit", (e) => this._evtSubmit(e));

        this._decorateTokenForms();
        this._decorateTokenNoteChangeLinks();
    }

    _decorateTokenForms() {
        this._tokenFormNodes = [];
        for (let i = 0; i < this._tokens.length; i++) {
            let formNode = this._hostNode.querySelector(
                '.token[data-token-id="' + i + '"]'
            );
            formNode.addEventListener("submit", (e) => this._evtDelete(e));
            this._tokenFormNodes.push(formNode);
        }
    }

    _decorateTokenNoteChangeLinks() {
        for (let i = 0; i < this._tokens.length; i++) {
            let linkNode = this._hostNode.querySelector(
                '.token-change-note[data-token-id="' + i + '"]'
            );
            linkNode.addEventListener("click", (e) =>
                this._evtChangeNoteClick(e)
            );
        }
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
        for (let formNode of this._tokenFormNodes) {
            views.enableForm(formNode);
        }
    }

    disableForm() {
        views.disableForm(this._formNode);
        for (let formNode of this._tokenFormNodes) {
            views.disableForm(formNode);
        }
    }

    _evtDelete(e) {
        e.preventDefault();
        const userToken =
            this._tokens[parseInt(e.target.getAttribute("data-token-id"))];
        this.dispatchEvent(
            new CustomEvent("delete", {
                detail: {
                    user: this._user,
                    userToken: userToken,
                },
            })
        );
    }

    _evtSubmit(e) {
        e.preventDefault();
        this.dispatchEvent(
            new CustomEvent("submit", {
                detail: {
                    user: this._user,

                    note: this._userTokenNoteInputNode
                        ? this._userTokenNoteInputNode.value
                        : undefined,

                    expirationTime:
                        this._userTokenExpirationTimeInputNode &&
                        this._userTokenExpirationTimeInputNode.value
                            ? new Date(
                                  this._userTokenExpirationTimeInputNode.value
                              ).toISOString()
                            : undefined,
                },
            })
        );
    }

    _evtChangeNoteClick(e) {
        e.preventDefault();
        const userToken =
            this._tokens[parseInt(e.target.getAttribute("data-token-id"))];
        const text = window.prompt(
            "Please enter the new name:",
            userToken.note !== null ? userToken.note : undefined
        );
        if (!text) {
            return;
        }
        this.dispatchEvent(
            new CustomEvent("update", {
                detail: {
                    user: this._user,
                    userToken: userToken,
                    note: text ? text : undefined,
                },
            })
        );
    }

    get _formNode() {
        return this._hostNode.querySelector("#create-token-form");
    }

    get _userTokenNoteInputNode() {
        return this._formNode.querySelector(".note input");
    }

    get _userTokenExpirationTimeInputNode() {
        return this._formNode.querySelector(".expirationTime input");
    }
}

module.exports = UserTokenView;
