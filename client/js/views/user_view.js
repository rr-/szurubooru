'use strict';

const views = require('../util/views.js');
const UserDeleteView = require('./user_delete_view.js');
const UserSummaryView = require('./user_summary_view.js');
const UserEditView = require('./user_edit_view.js');

class UserView {
    constructor() {
        this._template = views.getTemplate('user');
        this._deleteView = new UserDeleteView();
        this._summaryView = new UserSummaryView();
        this._editView = new UserEditView();
    }

    render(ctx) {
        const target = document.getElementById('content-holder');
        const source = this._template(ctx);

        ctx.section = ctx.section || 'summary';

        for (let item of source.querySelectorAll('[data-name]')) {
            if (item.getAttribute('data-name') === ctx.section) {
                item.className = 'active';
            } else {
                item.className = '';
            }
        }

        let view = null;
        if (ctx.section == 'edit') {
            view = this._editView;
        } else if (ctx.section == 'delete') {
            view = this._deleteView;
        } else {
            view = this._summaryView;
        }
        ctx.target = source.querySelector('#user-content-holder');
        view.render(ctx);

        views.listenToMessages(source);
        views.showView(target, source);
    }
}

module.exports = UserView;
