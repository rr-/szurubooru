'use strict';

const page = require('page');
const api = require('../api.js');
const events = require('../events.js');
const topNavController = require('../controllers/top_nav_controller.js');
const LoginView = require('../views/login_view.js');
const PasswordResetView = require('../views/password_reset_view.js');

class AuthController {
    constructor() {
        this.loginView = new LoginView();
        this.passwordResetView = new PasswordResetView();
    }

    registerRoutes() {
        page(/\/password-reset\/([^:]+):([^:]+)$/,
            (ctx, next) => {
                this.passwordResetFinishRoute(ctx.params[0], ctx.params[1]);
            });
        page('/password-reset', (ctx, next) => { this.passwordResetRoute(); });
        page('/login', (ctx, next) => { this.loginRoute(); });
        page('/logout', (ctx, next) => { this.logoutRoute(); });
    }

    loginRoute() {
        topNavController.activate('login');
        this.loginView.render({
            login: (name, password, doRemember) => {
                return new Promise((resolve, reject) => {
                    api.forget();
                    api.login(name, password, doRemember)
                        .then(() => {
                            resolve();
                            page('/');
                            events.notify(events.Success, 'Logged in');
                        }, errorMessage => {
                            reject(errorMessage);
                            events.notify(events.Error, errorMessage);
                        });
                });
            }});
    }

    logoutRoute() {
        api.forget();
        api.logout();
        page('/');
        events.notify(events.Success, 'Logged out');
    }

    passwordResetRoute() {
        topNavController.activate('login');
        this.passwordResetView.render({
            proceed: (...args) => {
                return this._passwordReset(...args);
            }});
    }

    passwordResetFinishRoute(name, token) {
        api.forget();
        api.logout();
        api.post('/password-reset/' + name, {token: token})
            .then(response => {
                const password = response.password;
                return api.login(name, password, false);
            }, response => {
                return Promise.reject(response.description);
            }).then(() => {
                page('/');
                events.notify(events.Success, 'New password: ' + password);
            }, errorMessage => {
                page('/');
                events.notify(events.Error, errorMessage);
            });
    }

    _passwordReset(nameOrEmail) {
        api.forget();
        api.logout();
        return new Promise((resolve, reject) => {
            api.get('/password-reset/' + nameOrEmail)
                .then(() => {
                    resolve();
                    events.notify(
                        events.Success,
                        'E-mail has been sent. To finish the procedure, ' +
                        'please click the link it contains.');
                }, response => {
                    reject();
                    events.notify(events.Error, response.description);
                });
        });
    }
}

module.exports = new AuthController();
