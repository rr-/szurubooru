'use strict';

const config = require('../config.js');
const events = require('../events.js');
const BaseView = require('./base_view.js');

class RegistrationView extends BaseView {
    constructor() {
        super();
        this.template = this.getTemplate('user-registration-template');
    }

    render(options) {
        this.showView(this.template());

        const form = this.contentHolder.querySelector('form');
        const userNameField = this.contentHolder.querySelector('#user-name');
        const passwordField = this.contentHolder.querySelector('#user-password');
        const emailField = this.contentHolder.querySelector('#user-email');

        this.decorateValidator(form);
        userNameField.setAttribute('pattern', config.userNameRegex);
        passwordField.setAttribute('pattern', config.passwordRegex);

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
                    events.notify(events.Error, errorMessage);
                });
        });
    }
}

module.exports = RegistrationView;
