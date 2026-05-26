"use strict";

const events = require("../events.js");
const api = require("../api.js");
const views = require("../util/views.js");
const FileDropperControl = require("../controls/file_dropper_control.js");
const TagInputControl = require("../controls/tag_input_control.js")
const misc = require("../util/misc.js");

const template = views.getTemplate("user-edit");

class UserEditView extends events.EventTarget {
    constructor(ctx) {
        super();

        ctx.userNamePattern = api.getUserNameRegex() + /|^$/.source;
        ctx.passwordPattern = api.getPasswordRegex() + /|^$/.source;

        this._user = ctx.user;
        this._hostNode = ctx.hostNode;
        views.replaceContent(this._hostNode, template(ctx));
        views.decorateValidator(this._formNode);

        this._avatarContent = null;
        if (this._avatarContentInputNode) {
            this._avatarFileDropper = new FileDropperControl(
                this._avatarContentInputNode,
                { lock: true }
            );
            this._avatarFileDropper.addEventListener("fileadd", (e) => {
                this._hostNode.querySelector(
                    "[name=avatar-style][value=manual]"
                ).checked = true;
                this._avatarContent = e.detail.files[0];
            });
        }

        for (let node of this._formNode.querySelectorAll("input, select")) {
            node.addEventListener("change", (e) => {
                if (!e.target.classList.contains("anticomplete")) {
                    this.dispatchEvent(new CustomEvent("change"));
                }
            });
        }

        if (this._blocklistFieldNode) {
            new TagInputControl(
                this._blocklistFieldNode,
                this._user.blocklist
            );
        }

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

                    name: this._userNameInputNode
                        ? this._userNameInputNode.value
                        : undefined,

                    email: this._emailInputNode
                        ? this._emailInputNode.value
                        : undefined,

                    rank: this._rankInputNode
                        ? this._rankInputNode.value
                        : undefined,

                    blocklist: this._blocklistFieldNode
                        ? misc.splitByWhitespace(this._blocklistFieldNode.value)
                        : undefined,

                    avatarStyle: this._avatarStyleInputNode
                        ? this._avatarStyleInputNode.value
                        : undefined,

                    password: this._passwordInputNode
                        ? this._passwordInputNode.value
                        : undefined,

                    avatarContent: this._avatarContent,
                },
            })
        );
    }

    get _formNode() {
        return this._hostNode.querySelector("form");
    }

    get _blocklistFieldNode() {
        return this._formNode.querySelector(".blocklist input");
    }

    get _rankInputNode() {
        return this._formNode.querySelector("[name=rank]");
    }

    get _emailInputNode() {
        return this._formNode.querySelector("[name=email]");
    }

    get _userNameInputNode() {
        return this._formNode.querySelector("[name=name]");
    }

    get _passwordInputNode() {
        return this._formNode.querySelector("[name=password]");
    }

    get _avatarContentInputNode() {
        return this._formNode.querySelector("#avatar-content");
    }

    get _avatarStyleInputNode() {
        return this._formNode.querySelector("[name=avatar-style]:checked");
    }
}

module.exports = UserEditView;
