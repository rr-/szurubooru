'use strict';

const BaseView = require('./base_view.js');

class UserSummaryView extends BaseView {
    constructor() {
        super();
        this.template = this.getTemplate('user-summary-template');
    }

    render(options) {
        options.target.innerHTML = this.template({
            user: options.user, isPrivate: options.isPrivate});
    }
}

module.exports = UserSummaryView;
