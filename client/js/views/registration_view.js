'use strict';

const config = require('../config.js');
const events = require('../events.js');
const BaseView = require('./base_view.js');

class RegistrationView extends BaseView {
    constructor() {
        super();
        this.template = this.getTemplate('user-registration-template');
    }

    render(ctx) {
        const target = this.contentHolder;
        const source = this.template();

        const form = source.querySelector('form');
        const userNameField = source.querySelector('#user-name');
        const passwordField = source.querySelector('#user-password');
        const emailField = source.querySelector('#user-email');

        this.decorateValidator(form);
        userNameField.setAttribute('pattern', config.userNameRegex);
        passwordField.setAttribute('pattern', config.passwordRegex);

        form.addEventListener('submit', e => {
            e.preventDefault();
            this.clearMessages();
            this.disableForm(form);
            ctx.register(
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

        this.showView(target, source);
    }
}

module.exports = RegistrationView;
