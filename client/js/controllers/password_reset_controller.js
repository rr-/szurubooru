'use strict';

const router = require('../router.js');
const api = require('../api.js');
const topNavigation = require('../models/top_navigation.js');
const PasswordResetView = require('../views/password_reset_view.js');

class PasswordResetController {
    constructor() {
        topNavigation.activate('login');

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
            }, response => {
                this._passwordResetView.showError(response.description);
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
            }, response => {
                return Promise.reject(response.description);
            }).then(() => {
                const ctx = router.show('/');
                ctx.controller.showSuccess('New password: ' + password);
            }, errorMessage => {
                const ctx = router.show('/');
                ctx.controller.showError(errorMessage);
            });
    }
}

module.exports = router => {
    router.enter('/password-reset', (ctx, next) => {
        ctx.controller = new PasswordResetController();
    });
    router.enter(/\/password-reset\/([^:]+):([^:]+)$/, (ctx, next) => {
        ctx.controller = new PasswordResetFinishController(
            ctx.params[0], ctx.params[1]);
    });
};
