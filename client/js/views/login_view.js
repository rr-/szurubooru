'use strict';

const config = require('../config.js');
const events = require('../events.js');
const BaseView = require('./base_view.js');

class LoginView extends BaseView {
    constructor() {
        super();
        this.template = this.getTemplate('login-template');
    }

    render(options) {
        this.showView(this.template());
        const form = this.contentHolder.querySelector('form');
        this.decorateValidator(form);

        const userNameField = document.getElementById('user-name');
        const passwordField = document.getElementById('user-password');
        const rememberUserField = document.getElementById('remember-user');
        userNameField.setAttribute('pattern', config.userNameRegex);
        passwordField.setAttribute('pattern', config.passwordRegex);

        form.addEventListener('submit', e => {
            e.preventDefault();
            this.clearMessages();
            this.disableForm(form);
            options
                .login(
                    userNameField.value,
                    passwordField.value,
                    rememberUserField.checked)
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

module.exports = LoginView;
