'use strict';

const config = require('../config.js');
const views = require('../util/views.js');

class RegistrationView {
    constructor() {
        this.template = views.getTemplate('user-registration');
    }

    render(ctx) {
        ctx.userNamePattern = config.userNameRegex;
        ctx.passwordPattern = config.passwordRegex;

        const target = document.getElementById('content-holder');
        const source = this.template(ctx);

        const form = source.querySelector('form');
        const userNameField = source.querySelector('#user-name');
        const passwordField = source.querySelector('#user-password');
        const emailField = source.querySelector('#user-email');

        views.decorateValidator(form);

        form.addEventListener('submit', e => {
            e.preventDefault();
            views.clearMessages(target);
            views.disableForm(form);
            ctx.register(
                    userNameField.value,
                    passwordField.value,
                    emailField.value)
                .always(() => { views.enableForm(form); });
        });

        views.listenToMessages(target);
        views.showView(target, source);
    }
}

module.exports = RegistrationView;
