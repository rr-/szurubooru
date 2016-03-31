'use strict';

const page = require('page');
const api = require('../api.js');
const topNavController = require('../controllers/top_nav_controller.js');
const RegistrationView = require('../views/registration_view.js');

class UsersController {
    constructor() {
        this.registrationView = new RegistrationView();
    }

    listUsersRoute() {
        topNavController.activate('users');
    }

    createUserRoute() {
        topNavController.activate('register');
        this.registrationView.render({register: this._register});
    }

    _register(name, password, email) {
        const data = {
            'name': name,
            'password': password,
            'email': email
        };
        // TODO: reduce callback hell
        return new Promise((resolve, reject) => {
            api.post('/users/', data).then(() => {
                api.login(name, password).then(() => {
                    resolve();
                    page('/');
                }).catch(response => {
                    reject(response.description);
                });
            }).catch(response => {
                reject(response.description);
            });
        });
    }

    showUserRoute(user) {
        if (api.isLoggedIn() &&
                user == api.getCurrentUser().name) {
            topNavController.activate('account');
        } else {
            topNavController.activate('users');
        }
    }

    editUserRoute(user) {
        topNavController.activate('users');
    }
}

module.exports = new UsersController();
