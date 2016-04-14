'use strict';

const views = require('../util/views.js');

class UserListPageView {
    constructor() {
        this.template = views.getTemplate('user-list-page');
    }

    render(ctx) {
        const target = ctx.target;
        const source = this.template(ctx);
        views.showView(target, source);
    }
}

module.exports = UserListPageView;
