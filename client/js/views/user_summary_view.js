'use strict';

const views = require('../util/views.js');

class UserSummaryView {
    constructor() {
        this._template = views.getTemplate('user-summary');
    }

    render(ctx) {
        const target = ctx.target;
        const source = this._template(ctx);
        views.listenToMessages(source);
        views.showView(target, source);
    }
}

module.exports = UserSummaryView;
