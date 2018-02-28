'use strict';

const events = require('../events.js');
const views = require('../util/views.js');

const template = views.getTemplate('user-tokens');

class UserTokenView extends events.EventTarget {
    constructor(ctx) {
        super();

        this._user = ctx.user;
        this._tokens = ctx.tokens;
        this._hostNode = ctx.hostNode;
        this._tokenFormNodes = [];
        views.replaceContent(this._hostNode, template(ctx));
        views.decorateValidator(this._formNode);

        this._formNode.addEventListener('submit', e => this._evtSubmit(e));

        this._decorateTokenForms()
    }

    _decorateTokenForms() {
        for (let i = 0; i < this._tokens.length; i++) {
            let formNode = this._hostNode.querySelector('#token' + i);
            views.decorateValidator(formNode);
            formNode.addEventListener('submit', e => this._evtDelete(e));
            this._tokenFormNodes.push(formNode)
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
        for (let i = 0; i < this._tokenFormNodes.length; i++) {
            let formNode = this._tokenFormNodes[i];
            views.enableForm(formNode);
        }
    }

    disableForm() {
        views.disableForm(this._formNode);
        for (let i = 0; i < this._tokenFormNodes.length; i++) {
            let formNode = this._tokenFormNodes[i];
            views.disableForm(formNode);
        }
    }

    _evtDelete(e) {
        e.preventDefault();
        const userToken = this._tokens[parseInt(e.target.id.replace('token', ''))];
        this.dispatchEvent(new CustomEvent('delete', {
            detail: {
                user: this._user,
                userToken: userToken,
            },
        }));
    }

    _evtSubmit(e) {
        e.preventDefault();
        this.dispatchEvent(new CustomEvent('submit', {
            detail: {
                user: this._user
            },
        }));
    }

    get _formNode() {
        return this._hostNode.querySelector('#create-token-form');
    }
}

module.exports = UserTokenView;
