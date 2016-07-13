'use strict';

const events = require('../events.js');
const views = require('../util/views.js');
const UserDeleteView = require('./user_delete_view.js');
const UserSummaryView = require('./user_summary_view.js');
const UserEditView = require('./user_edit_view.js');

const template = views.getTemplate('user');

class UserView extends events.EventTarget {
    constructor(ctx) {
        super();

        this._ctx = ctx;
        ctx.user.addEventListener('change', e => this._evtChange(e));
        ctx.section = ctx.section || 'summary';

        this._hostNode = document.getElementById('content-holder');
        this._install();
    }

    _install() {
        const ctx = this._ctx;
        views.replaceContent(this._hostNode, template(ctx));
        for (let item of this._hostNode.querySelectorAll('[data-name]')) {
            if (item.getAttribute('data-name') === ctx.section) {
                item.className = 'active';
            } else {
                item.className = '';
            }
        }

        ctx.hostNode = this._hostNode.querySelector('#user-content-holder');
        if (ctx.section == 'edit') {
            this._view = new UserEditView(ctx);
            this._view.addEventListener('submit', e => {
                this.dispatchEvent(
                    new CustomEvent('change', {detail: e.detail}));
            });
        } else if (ctx.section == 'delete') {
            this._view = new UserDeleteView(ctx);
            this._view.addEventListener('submit', e => {
                this.dispatchEvent(
                    new CustomEvent('delete', {detail: e.detail}));
            });
        } else {
            this._view = new UserSummaryView(ctx);
        }

        views.syncScrollPosition();
    }

    clearMessages() {
        this._view.clearMessages();
    }

    showSuccess(message) {
        this._view.showSuccess(message);
    }

    showError(message) {
        this._view.showError(message);
    }

    enableForm() {
        this._view.enableForm();
    }

    disableForm() {
        this._view.disableForm();
    }

    _evtChange(e) {
        this._ctx.user = e.detail.user;
        this._install(this._ctx);
    }
}

module.exports = UserView;
