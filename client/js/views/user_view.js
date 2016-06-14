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

        this._hostNode = document.getElementById('content-holder');
        ctx.section = ctx.section || 'summary';
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
}

module.exports = UserView;
