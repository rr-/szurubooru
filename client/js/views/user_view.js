'use strict';

const BaseView = require('./base_view.js');
const UserSummaryView = require('./user_summary_view.js');
const UserEditView = require('./user_edit_view.js');

class UserView extends BaseView {
    constructor() {
        super();
        this.template = this.getTemplate('user-template');
        this.summaryView = new UserSummaryView();
        this.editView = new UserEditView();
    }

    render(options) {
        let section = options.section;
        if (!section) {
            section = 'summary';
        }

        let view = null;
        if (section == 'edit') {
            view = this.editView;
        } else {
            view = this.summaryView;
        }

        this.showView(this.template(options));

        options.target = this.contentHolder.querySelector(
            '#user-content-holder');
        view.render(options);

        const allItemsSelector = '#content-holder [data-name]';
        for (let item of document.querySelectorAll(allItemsSelector)) {
            if (item.getAttribute('data-name') === section) {
                item.className = 'active';
            } else {
                item.className = '';
            }
        }
    }
}

module.exports = UserView;
