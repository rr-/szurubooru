'use strict';

const cookies = require('js-cookie');
const page = require('page');
const api = require('../api.js');
const topNavController = require('../controllers/top_nav_controller.js');
const LoginView = require('../views/login_view.js');

class AuthController {
    constructor() {
        this.loginView = new LoginView();

        const auth = cookies.getJSON('auth');
        if (auth && auth.user && auth.password) {
            api.login(auth.user, auth.password).catch(errorMessage => {
                cookies.remove('auth');
                page('/');
                this.loginView.notifyError(
                    'An error happened while trying to log you in: ' +
                    errorMessage);
            });
        }
    }

    loginRoute() {
        topNavController.activate('login');
        this.loginView.render({
            login: (name, password, doRemember) => {
                return new Promise((resolve, reject) => {
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
                            this.loginView.notifySuccess('Logged in');
                        }).catch(errorMessage => { reject(errorMessage); });
                });
            }});
    }

    logoutRoute() {
        api.logout();
        cookies.remove('auth');
        page('/');
        this.loginView.notifySuccess('Logged out');
    }
}

module.exports = new AuthController();
