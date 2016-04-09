'use strict';

const BaseView = require('./base_view.js');

class UserDeletionView extends BaseView {
    constructor() {
        super();
        this.template = this.getTemplate('user-deletion-template');
    }

    render(ctx) {
        const target = ctx.target;
        const source = this.template(ctx);

        const form = source.querySelector('form');

        this.decorateValidator(form);

        form.addEventListener('submit', e => {
            e.preventDefault();
            this.clearMessages();
            this.disableForm(form);
            ctx.delete();
        });

        this.showView(target, source);
    }
}

module.exports = UserDeletionView;
