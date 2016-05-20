'use strict';

const config = require('../config.js');
const views = require('../util/views.js');
const FileDropperControl = require('../controls/file_dropper_control.js');

class UserEditView {
    constructor() {
        this._template = views.getTemplate('user-edit');
    }

    render(ctx) {
        ctx.userNamePattern = config.userNameRegex + /|^$/.source;
        ctx.passwordPattern = config.passwordRegex + /|^$/.source;

        const target = ctx.target;
        const source = this._template(ctx);

        const form = source.querySelector('form');
        const avatarContentField = source.querySelector('#avatar-content');

        views.decorateValidator(form);

        let avatarContent = null;
        if (avatarContentField) {
            new FileDropperControl(
                avatarContentField,
                {
                    lock: true,
                    resolve: files => {
                        source.querySelector(
                            '[name=avatar-style][value=manual]').checked = true;
                        avatarContent = files[0];
                    },
                });
        }

        form.addEventListener('submit', e => {
            const rankField = source.querySelector('#user-rank');
            const emailField = source.querySelector('#user-email');
            const userNameField = source.querySelector('#user-name');
            const passwordField = source.querySelector('#user-password');
            const avatarStyleField = source.querySelector(
                '[name=avatar-style]:checked');

            e.preventDefault();
            views.clearMessages(target);
            views.disableForm(form);
            ctx.edit({
                    name: userNameField.value,
                    password: passwordField.value,
                    email: emailField.value,
                    rank: rankField.value,
                    avatarStyle: avatarStyleField.value,
                    avatarContent: avatarContent})
                .always(() => { views.enableForm(form); });
        });

        views.listenToMessages(source);
        views.showView(target, source);
    }
}

module.exports = UserEditView;
