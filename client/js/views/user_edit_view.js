'use strict';

const config = require('../config.js');
const views = require('../util/views.js');

class UserEditView {
    constructor() {
        this.template = views.getTemplate('user-edit');
    }

    render(ctx) {
        const target = ctx.target;
        const source = this.template(ctx);

        const form = source.querySelector('form');
        const rankField = source.querySelector('#user-rank');
        const emailField = source.querySelector('#user-email');
        const userNameField = source.querySelector('#user-name');
        const passwordField = source.querySelector('#user-password');

        views.decorateValidator(form);

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
            views.clearMessages(target);
            views.disableForm(form);
            ctx.edit(
                    userNameField.value,
                    passwordField.value,
                    emailField.value,
                    rankField.value)
                .always(() => { views.enableForm(form); });
        });

        views.listenToMessages(target);
        views.showView(target, source);
    }
}

module.exports = UserEditView;
