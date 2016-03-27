'use strict';

const config = require('../config.js');
const BaseView = require('./base_view.js');

class LoginView extends BaseView {
    constructor(handlebars) {
        super(handlebars);
        this.template = this.getTemplate('login-template');
    }

    render(options) {
        this.showView(this.template());
        const form = document.querySelector('#content-holder form');
        this.decorateValidator(form);

        const userNameField = document.getElementById('user-name');
        const passwordField = document.getElementById('user-password');
        userNameField.setAttribute('pattern', config.service.userNameRegex);
        passwordField.setAttribute('pattern', config.service.passwordRegex);

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            options.login(userNameField.value, passwordField.value);
        });
    }
}

module.exports = LoginView;
