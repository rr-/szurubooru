'use strict';

const config = require('../config.js');
const BaseView = require('./base_view.js');

class RegistrationView extends BaseView {
    constructor(handlebars) {
        super(handlebars);
        this.template = this.getTemplate('user-registration-template');
    }

    render(settings) {
        this.showView(this.template());
        const form = document.querySelector('#content-holder form');
        this.decorateValidator(form);

        const userNameField = document.getElementById('user-name');
        const passwordField = document.getElementById('user-password');
        const emailField = document.getElementById('user-email');
        userNameField.setAttribute('pattern', config.service.userNameRegex);
        passwordField.setAttribute('pattern', config.service.passwordRegex);

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const user = {
                name: userNameField.value,
                password: passwordField.value,
                email: emailField.value,
            };
            settings.onRegistered(user);
        });
    }
}

module.exports = RegistrationView;
