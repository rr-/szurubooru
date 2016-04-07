'use strict';

const config = require('../config.js');
const BaseView = require('./base_view.js');

class UserEditView extends BaseView {
    constructor() {
        super();
        this.template = this.getTemplate('user-edit-template');
    }

    render(options) {
        options.target.innerHTML = this.template(options);

        const form = options.target.querySelector('form');
        const rankField = options.target.querySelector('#user-rank');
        const emailField = options.target.querySelector('#user-email');
        const userNameField = options.target.querySelector('#user-name');
        const passwordField = options.target.querySelector('#user-password');

        this.decorateValidator(form);

        if (userNameField) {
            userNameField.setAttribute(
                'pattern',
                config.userNameRegex + /|^$/.source);
        }

        if (passwordField) {
            passwordField.setAttribute(
                'pattern',
                config.passwordRegex + /|^$/.source);
        }

        if (rankField) {
            rankField.value = options.user.rank;
        }

        /* TODO: avatar */

        form.addEventListener('submit', e => {
            e.preventDefault();
            this.clearMessages();
            this.disableForm(form);
            options
                .edit(
                    userNameField.value,
                    passwordField.value,
                    emailField.value,
                    rankField.value)
                .then(user => { this.enableForm(form); })
                .catch(() => { this.enableForm(form); });
        });
    }
}

module.exports = UserEditView;
