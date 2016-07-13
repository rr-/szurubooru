'use strict';

const config = require('../config.js');
const events = require('../events.js');
const views = require('../util/views.js');

const template = views.getTemplate('login');

class LoginView extends events.EventTarget {
    constructor() {
        super();
        this._hostNode = document.getElementById('content-holder');

        views.replaceContent(this._hostNode, template({
            userNamePattern: config.userNameRegex,
            passwordPattern: config.passwordRegex,
            canSendMails: config.canSendMails,
        }));
        views.syncScrollPosition();

        views.decorateValidator(this._formNode);
        this._userNameFieldNode.setAttribute('pattern', config.userNameRegex);
        this._passwordFieldNode.setAttribute('pattern', config.passwordRegex);
        this._formNode.addEventListener('submit', e => {
            e.preventDefault();
            this.dispatchEvent(new CustomEvent('submit', {
                detail: {
                    name: this._userNameFieldNode.value,
                    password: this._passwordFieldNode.value,
                    remember: this._rememberFieldNode.checked,
                },
            }));
        });
    }

    get _formNode() {
        return this._hostNode.querySelector('form');
    }

    get _userNameFieldNode() {
        return this._formNode.querySelector('#user-name');
    }

    get _passwordFieldNode() {
        return this._formNode.querySelector('#user-password');
    }

    get _rememberFieldNode() {
        return this._formNode.querySelector('#remember-user');
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
