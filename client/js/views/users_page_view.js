'use strict';

const views = require('../util/views.js');

class UsersPageView {
    constructor() {
        this.template = views.getTemplate('users-page');
    }

    render(ctx) {
        const target = ctx.target;
        const source = this.template(ctx);
        views.showView(target, source);
    }
}

module.exports = UsersPageView;
