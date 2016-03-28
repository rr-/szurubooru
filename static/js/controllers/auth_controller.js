'use strict';

const page = require('page');
const config = require('../config.js');

class AuthController {
    constructor(api, topNavigationController, loginView) {
        this.api = api;
        this.topNavigationController = topNavigationController;
        this.loginView = loginView;
        this.currentUser = null;
        /* TODO: load from cookies */
    }

    isLoggedIn() {
        return this.currentUser !== null;
    }

    hasPrivilege() {
        return true;
    }

    login(userName, userPassword) {
        return new Promise((resolve, reject) => {
            this.api.userName = userName;
            this.api.userPassword = userPassword;
            this.api.get('/user/' + userName)
                .then(resolve)
                .catch(reject);
        });
    }

    logout(user) {
        this.currentUser = null;
        this.api.userName = null;
        this.api.userPassword = null;
        /* TODO: clear cookie */
    }

    loginRoute() {
        this.topNavigationController.activate('login');
        this.loginView.render({
            login: (userName, userPassword, doRemember) => {
                return new Promise((resolve, reject) => {
                    this
                        .login(userName, userPassword)
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
    }
}

module.exports = AuthController;
