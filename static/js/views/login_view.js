'use strict';

const config = require('../config.js');
const BaseView = require('./base_view.js');

class LoginView extends BaseView {
    constructor() {
        super();
        this.template = this.getTemplate('login-template');
    }

    render(options) {
        this.showView(this.template());
        const messagesHolder = this.contentHolder.querySelector('.messages');
        const form = this.contentHolder.querySelector('form');
        this.decorateValidator(form);

        const userNameField = document.getElementById('user-name');
        const passwordField = document.getElementById('user-password');
        const rememberUserField = document.getElementById('remember-user');
        userNameField.setAttribute('pattern', config.service.userNameRegex);
        passwordField.setAttribute('pattern', config.service.passwordRegex);

        form.addEventListener('submit', e => {
            e.preventDefault();
            this.clearMessages(messagesHolder);
            form.setAttribute('disabled', true);
            options
                .login(
                    userNameField.value,
                    passwordField.value,
                    rememberUserField.checked)
                .then(() => {
                    form.setAttribute('disabled', false);
                })
                .catch(errorMessage => {
                    form.setAttribute('disabled', false);
                    this.showError(messagesHolder, errorMessage);
                });
        });
    }
}

module.exports = LoginView;
