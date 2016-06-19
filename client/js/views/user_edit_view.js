'use strict';

const config = require('../config.js');
const events = require('../events.js');
const views = require('../util/views.js');
const FileDropperControl = require('../controls/file_dropper_control.js');

const template = views.getTemplate('user-edit');

class UserEditView extends events.EventTarget {
    constructor(ctx) {
        super();

        ctx.userNamePattern = config.userNameRegex + /|^$/.source;
        ctx.passwordPattern = config.passwordRegex + /|^$/.source;

        this._user = ctx.user;
        this._hostNode = ctx.hostNode;
        views.replaceContent(this._hostNode, template(ctx));
        views.decorateValidator(this._formNode);

        this._avatarContent = null;
        if (this._avatarContentFieldNode) {
            new FileDropperControl(
                this._avatarContentFieldNode,
                {
                    lock: true,
                    resolve: files => {
                        this._hostNode.querySelector(
                            '[name=avatar-style][value=manual]').checked = true;
                        this._avatarContent = files[0];
                    },
                });
        }

        this._formNode.addEventListener('submit', e => this._evtSubmit(e));
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
        this.dispatchEvent(new CustomEvent('submit', {
            detail: {
                user:          this._user,
                name:          (this._userNameFieldNode || {}).value,
                email:         (this._emailFieldNode || {}).value,
                rank:          (this._rankFieldNode || {}).value,
                avatarStyle:   (this._avatarStyleFieldNode || {}).value,
                password:      (this._passwordFieldNode || {}).value,
                avatarContent: this._avatarContent,
            },
        }));
    }

    get _formNode() {
        return this._hostNode.querySelector('form');
    }

    get _rankFieldNode() {
        return this._formNode.querySelector('#user-rank');
    }

    get _emailFieldNode() {
        return this._formNode.querySelector('#user-email');
    }

    get _userNameFieldNode() {
        return this._formNode.querySelector('#user-name');
    }

    get _passwordFieldNode() {
        return this._formNode.querySelector('#user-password');
    }

    get _avatarContentFieldNode() {
        return this._formNode.querySelector('#avatar-content');
    }

    get _avatarStyleFieldNode() {
        return this._formNode.querySelector('[name=avatar-style]:checked');
    }
}

module.exports = UserEditView;
