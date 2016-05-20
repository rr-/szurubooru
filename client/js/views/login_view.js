'use strict';

const config = require('../config.js');
const views = require('../util/views.js');

class LoginView {
    constructor() {
        this._template = views.getTemplate('login');
    }

    render(ctx) {
        const target = document.getElementById('content-holder');
        const source = this._template({
            userNamePattern: config.userNameRegex,
            passwordPattern: config.passwordRegex,
            canSendMails: config.canSendMails,
        });

        const form = source.querySelector('form');
        const userNameField = source.querySelector('#user-name');
        const passwordField = source.querySelector('#user-password');
        const rememberUserField = source.querySelector('#remember-user');

        views.decorateValidator(form);
        userNameField.setAttribute('pattern', config.userNameRegex);
        passwordField.setAttribute('pattern', config.passwordRegex);

        form.addEventListener('submit', e => {
            e.preventDefault();
            views.clearMessages(target);
            views.disableForm(form);
            ctx.login(
                    userNameField.value,
                    passwordField.value,
                    rememberUserField.checked)
                .always(() => { views.enableForm(form); });
        });

        views.listenToMessages(source);
        views.showView(target, source);
    }
}

module.exports = LoginView;
