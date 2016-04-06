'use strict';

const BaseView = require('./base_view.js');

class PasswordResetView extends BaseView {
    constructor() {
        super();
        this.template = this.getTemplate('password-reset-template');
    }

    render(options) {
        this.showView(this.template());
        const form = this.contentHolder.querySelector('form');
        this.decorateValidator(form);

        const userNameOrEmailField = document.getElementById('user-name');

        form.addEventListener('submit', e => {
            e.preventDefault();
            this.clearMessages();
            this.disableForm(form);
            options
                .proceed(userNameOrEmailField.value)
                .then(() => {
                    this.notifySuccess(
                        'E-mail has been sent. To finish the procedure, ' +
                        'please click the link it contains.');
                })
                .catch(errorMessage => {
                    this.enableForm(form);
                    this.notifyError(errorMessage);
                });
        });
    }
}

module.exports = PasswordResetView;
