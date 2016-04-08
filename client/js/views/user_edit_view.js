'use strict';

const config = require('../config.js');
const BaseView = require('./base_view.js');

class UserEditView extends BaseView {
    constructor() {
        super();
        this.template = this.getTemplate('user-edit-template');
    }

    render(ctx) {
        const target = ctx.target;
        const source = this.template(ctx);

        const form = source.querySelector('form');
        const rankField = source.querySelector('#user-rank');
        const emailField = source.querySelector('#user-email');
        const userNameField = source.querySelector('#user-name');
        const passwordField = source.querySelector('#user-password');

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
            rankField.value = ctx.user.rank;
        }

        /* TODO: avatar */

        form.addEventListener('submit', e => {
            e.preventDefault();
            this.clearMessages();
            this.disableForm(form);
            ctx.edit(
                    userNameField.value,
                    passwordField.value,
                    emailField.value,
                    rankField.value)
                .then(user => { this.enableForm(form); })
                .catch(() => { this.enableForm(form); });
        });

        this.showView(target, source);
    }
}

module.exports = UserEditView;
