'use strict';

const page = require('page');

class UsersController {
    constructor(
            api, topNavigationController, authController, registrationView) {
        this.api = api;
        this.topNavigationController = topNavigationController;
        this.authController = authController;
        this.registrationView = registrationView;
    }

    listUsersRoute() {
        this.topNavigationController.activate('users');
    }

    createUserRoute() {
        this.topNavigationController.activate('register');
        this.registrationView.render({
            register: (userName, userPassword, userEmail) => {
                const data = {
                    'name': userName,
                    'password': userPassword,
                    'email': userEmail
                };
                // TODO: reduce callback hell
                return new Promise((resolve, reject) => {
                    this.api.post('/users/', data)
                        .then(() => {
                            this.authController.login(userName, userPassword)
                                .then(() => {
                                    resolve();
                                    page('/');
                                })
                                .catch(response => {
                                    reject(response.description);
                                });
                        })
                        .catch(response => {
                            reject(response.description);
                        });
                });
            }});
    }

    showUserRoute(user) {
        if (this.authController.isLoggedIn() &&
                user == this.authController.getCurrentUser().name) {
            this.topNavigationController.activate('account');
        } else {
            this.topNavigationController.activate('users');
        }
    }

    editUserRoute(user) {
        this.topNavigationController.activate('users');
    }
}

module.exports = UsersController;
