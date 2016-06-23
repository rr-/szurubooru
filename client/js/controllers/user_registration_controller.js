'use strict';

const router = require('../router.js');
const api = require('../api.js');
const User = require('../models/user.js');
const topNavigation = require('../models/top_navigation.js');
const RegistrationView = require('../views/registration_view.js');

class UserRegistrationController {
    constructor() {
        topNavigation.activate('register');
        this._view = new RegistrationView();
        this._view.addEventListener('submit', e => this._evtRegister(e));
    }

    _evtRegister(e) {
        this._view.clearMessages();
        this._view.disableForm();
        const user = new User();
        user.name = e.detail.name;
        user.email = e.detail.email;
        user.password = e.detail.password;
        user.save().then(() => {
            api.forget();
            return api.login(e.detail.name, e.detail.password, false);
        }, errorMessage => {
            return Promise.reject(errorMessage);
        }).then(() => {
            const ctx = router.show('/');
            ctx.controller.showSuccess('Welcome aboard!');
        }, errorMessage => {
            this._view.showError(errorMessage);
            this._view.enableForm();
        });
    }
}

module.exports = router => {
    router.enter('/register', (ctx, next) => {
        new UserRegistrationController();
    });
};
