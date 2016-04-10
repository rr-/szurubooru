'use strict';

const views = require('../util/views.js');

class UserListView {
    constructor() {
        this.template = views.getTemplate('user-list');
    }

    render(ctx) {
        const target = ctx.target;
        const source = this.template(ctx);
        views.listenToMessages(target);
        views.showView(target, source);
    }
}

module.exports = UserListView;
