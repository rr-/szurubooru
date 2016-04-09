'use strict';

const BaseView = require('./base_view.js');
const UserDeletionView = require('./user_deletion_view.js');
const UserSummaryView = require('./user_summary_view.js');
const UserEditView = require('./user_edit_view.js');

class UserView extends BaseView {
    constructor() {
        super();
        this.template = this.getTemplate('user-template');
        this.deletionView = new UserDeletionView();
        this.summaryView = new UserSummaryView();
        this.editView = new UserEditView();
    }

    render(ctx) {
        const target = this.contentHolder;
        const source = this.template(ctx);

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
            view = this.editView;
        } else if (ctx.section == 'delete') {
            view = this.deletionView;
        } else {
            view = this.summaryView;
        }
        ctx.target = source.querySelector('#user-content-holder');
        view.render(ctx);

        this.showView(target, source);
    }
}

module.exports = UserView;
