'use strict';

const cookies = require('js-cookie');
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

    login() {
        return new Promise((resolve, reject) => {
            const auth = cookies.getJSON('auth');
            if (auth && auth.user && auth.password) {
                api.login(auth.user, auth.password)
                    .then(resolve)
                    .catch(errorMessage => {
                        reject(errorMessage);
                    });
            } else {
                resolve();
            }
        });
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
                    cookies.remove('auth');
                    api.login(name, password)
                        .then(() => {
                            const options = {};
                            if (doRemember) {
                                options.expires = 365;
                            }
                            cookies.set(
                                'auth',
                                {'user': name, 'password': password},
                                options);
                            resolve();
                            page('/');
                            events.notify(events.Success, 'Logged in');
                        }).catch(errorMessage => { reject(errorMessage); });
                });
            }});
    }

    logoutRoute() {
        api.logout();
        cookies.remove('auth');
        page('/');
        events.notify(events.Success, 'Logged out');
    }

    passwordResetRoute() {
        topNavController.activate('login');
        this.passwordResetView.render({
            proceed: nameOrEmail => {
                api.logout();
                cookies.remove('auth');
                return new Promise((resolve, reject) => {
                    api.get('/password-reset/' + nameOrEmail)
                        .then(() => { resolve(); })
                        .catch(errorMessage => { reject(errorMessage); });
                });
            }});
    }

    passwordResetFinishRoute(name, token) {
        api.logout();
        cookies.remove('auth');
        api.post('/password-reset/' + name, {token: token})
            .then(response => {
                const password = response.password;
                api.login(name, password)
                    .then(() => {
                        cookies.set(
                            'auth', {'user': name, 'password': password}, {});
                        page('/');
                        events.notify(events.Success,
                            'New password: ' + password);
                    }).catch(errorMessage => {
                        page('/');
                        events.notify(events.Error, errorMessage);
                    });
            }).catch(response => {
                page('/');
                events.notify(events.Error, response.description);
            });
    }
}

module.exports = new AuthController();
