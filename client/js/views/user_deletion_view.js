'use strict';

const views = require('../util/views.js');

class UserDeletionView {
    constructor() {
        this.template = views.getTemplate('user-deletion');
    }

    render(ctx) {
        const target = ctx.target;
        const source = this.template(ctx);

        const form = source.querySelector('form');

        views.decorateValidator(form);

        form.addEventListener('submit', e => {
            e.preventDefault();
            views.clearMessages(target);
            views.disableForm(form);
            ctx.delete();
        });

        views.listenToMessages(target);
        views.showView(target, source);
    }
}

module.exports = UserDeletionView;
