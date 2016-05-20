'use strict';

const views = require('../util/views.js');

class PasswordResetView {
    constructor() {
        this._template = views.getTemplate('password-reset');
    }

    render(ctx) {
        const target = document.getElementById('content-holder');
        const source = this._template();

        const form = source.querySelector('form');
        const userNameOrEmailField = source.querySelector('#user-name');

        views.decorateValidator(form);

        form.addEventListener('submit', e => {
            e.preventDefault();
            views.clearMessages(target);
            views.disableForm(form);
            ctx.proceed(userNameOrEmailField.value)
                .catch(() => { views.enableForm(form); });
        });

        views.listenToMessages(source);
        views.showView(target, source);
    }
}

module.exports = PasswordResetView;
