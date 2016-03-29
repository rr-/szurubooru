'use strict';

const config = require('../config.js');
const BaseView = require('./base_view.js');

class RegistrationView extends BaseView {
    constructor() {
        super();
        this.template = this.getTemplate('user-registration-template');
    }

    render(options) {
        this.showView(this.template());
        const messagesHolder = this.contentHolder.querySelector('.messages');
        const form = document.querySelector('#content-holder form');
        this.decorateValidator(form);

        const userNameField = document.getElementById('user-name');
        const passwordField = document.getElementById('user-password');
        const emailField = document.getElementById('user-email');
        userNameField.setAttribute('pattern', config.service.userNameRegex);
        passwordField.setAttribute('pattern', config.service.passwordRegex);

        form.addEventListener('submit', e => {
            e.preventDefault();
            this.clearMessages(messagesHolder);
            form.setAttribute('disabled', true);
            options
                .register(
                    userNameField.value,
                    passwordField.value,
                    emailField.value)
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

module.exports = RegistrationView;
