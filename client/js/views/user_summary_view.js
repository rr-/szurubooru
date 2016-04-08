'use strict';

const BaseView = require('./base_view.js');

class UserSummaryView extends BaseView {
    constructor() {
        super();
        this.template = this.getTemplate('user-summary-template');
    }

    render(ctx) {
        const target = ctx.target;
        const source = this.template(ctx);
        this.showView(target, source);
    }
}

module.exports = UserSummaryView;
