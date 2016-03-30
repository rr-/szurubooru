'use strict';

const page = require('page');
const config = require('../config.js');

class AuthController {
    constructor(api, topNavigationController, loginView) {
        this.api = api;
        this.topNavigationController = topNavigationController;
        this.loginView = loginView;
        /* TODO: load from cookies */
    }

    loginRoute() {
        this.topNavigationController.activate('login');
        this.loginView.render({
            login: (userName, userPassword, doRemember) => {
                return new Promise((resolve, reject) => {
                    this.api.login(userName, userPassword);
                    this.api.get('/user/' + userName)
                        .then(response => {
                            if (doRemember) {
                                /* TODO: set cookie */
                            }
                            resolve();
                            page('/');
                            /* TODO: update top navigation */
                        })
                        .catch(response => { reject(response.description); });
                });
            }});
    }

    logoutRoute() {
        this.topNavigationController.activate('logout');
        /* TODO: clear cookie */
    }
}

module.exports = AuthController;
