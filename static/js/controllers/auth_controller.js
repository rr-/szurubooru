'use strict';

const cookies = require('js-cookie');
const page = require('page');

class AuthController {
    constructor(api, topNavigationController, loginView) {
        this.api = api;
        this.topNavigationController = topNavigationController;
        this.loginView = loginView;

        const auth = cookies.getJSON('auth');
        if (auth && auth.user && auth.password) {
            this.api.login(auth.user, auth.password).catch(errorMessage => {
                cookies.remove('auth');
                page('/');
                this.loginView.notifyError(
                    'An error happened while trying to log you in: ' +
                    errorMessage);
            });
        }
    }

    loginRoute() {
        this.topNavigationController.activate('login');
        this.loginView.render({
            login: (name, password, doRemember) => {
                return new Promise((resolve, reject) => {
                    this.api.login(name, password)
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
                            this.loginView.notifySuccess('Logged in');
                        }).catch(errorMessage => { reject(errorMessage); });
                });
            }});
    }

    logoutRoute() {
        this.api.logout();
        cookies.remove('auth');
        page('/');
        this.loginView.notifySuccess('Logged out');
    }
}

module.exports = AuthController;
