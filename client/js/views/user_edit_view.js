'use strict';

const config = require('../config.js');
const views = require('../util/views.js');
const FileDropperControl = require('./file_dropper_control.js');

class UserEditView {
    constructor() {
        this.template = views.getTemplate('user-edit');
        this.fileDropperControl = new FileDropperControl();
    }

    render(ctx) {
        ctx.userNamePattern = config.userNameRegex + /|^$/.source;
        ctx.passwordPattern = config.passwordRegex + /|^$/.source;

        const target = ctx.target;
        const source = this.template(ctx);

        const form = source.querySelector('form');
        const avatarContentField = source.querySelector('#avatar-content');

        views.decorateValidator(form);

        let avatarContent = null;
        this.fileDropperControl.render({
            target: avatarContentField,
            lock: true,
            resolve: files => {
                source.querySelector(
                    '[name=avatar-style][value=manual]').checked = true;
                avatarContent = files[0];
            },
        });

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

        views.listenToMessages(target);
        views.showView(target, source);
    }
}

module.exports = UserEditView;
