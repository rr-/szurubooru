'use strict';

const router = require('../router.js');
const api = require('../api.js');
const topNavigation = require('../models/top_navigation.js');
const PasswordResetView = require('../views/password_reset_view.js');

class PasswordResetController {
    constructor() {
        topNavigation.activate('login');
        topNavigation.setTitle('Password reminder');

        this._passwordResetView = new PasswordResetView();
        this._passwordResetView.addEventListener(
            'submit', e => this._evtReset(e));
    }

    _evtReset(e) {
        this._passwordResetView.clearMessages();
        this._passwordResetView.disableForm();
        api.forget();
        api.logout();
        api.get('/password-reset/' + e.detail.userNameOrEmail)
            .then(() => {
                this._passwordResetView.showSuccess(
                    'E-mail has been sent. To finish the procedure, ' +
                    'please click the link it contains.');
            }, error => {
                this._passwordResetView.showError(error.message);
                this._passwordResetView.enableForm();
            });
    }
}

class PasswordResetFinishController {
    constructor(name, token) {
        api.forget();
        api.logout();
        let password = null;
        api.post('/password-reset/' + name, {token: token})
            .then(response => {
                password = response.password;
                return api.login(name, password, false);
            }).then(() => {
                const ctx = router.show('/');
                ctx.controller.showSuccess('New password: ' + password);
            }, error => {
                const ctx = router.show('/');
                ctx.controller.showError(error.message);
            });
    }
}

module.exports = router => {
    router.enter('/password-reset', (ctx, next) => {
        ctx.controller = new PasswordResetController();
    });
    router.enter(/\/password-reset\/([^:]+):([^:]+)$/, (ctx, next) => {
        ctx.controller = new PasswordResetFinishController(
            ctx.parameters[0], ctx.parameters[1]);
    });
};
