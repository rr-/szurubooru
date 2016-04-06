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
        const form = document.querySelector('#content-holder form');
        this.decorateValidator(form);

        const userNameField = document.getElementById('user-name');
        const passwordField = document.getElementById('user-password');
        const emailField = document.getElementById('user-email');
        userNameField.setAttribute('pattern', config.service.userNameRegex);
        passwordField.setAttribute('pattern', config.service.passwordRegex);

        form.addEventListener('submit', e => {
            e.preventDefault();
            this.clearMessages();
            this.disableForm(form);
            options
                .register(
                    userNameField.value,
                    passwordField.value,
                    emailField.value)
                .then(() => {
                    this.enableForm(form);
                })
                .catch(errorMessage => {
                    this.enableForm(form);
                    this.notifyError(errorMessage);
                });
        });
    }
}

module.exports = RegistrationView;
