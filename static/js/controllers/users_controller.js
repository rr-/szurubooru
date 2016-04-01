'use strict';

const cookies = require('js-cookie');
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
        this.registrationView.render({register: (...args) => {
            return this._register(...args);
        }});
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
                    cookies.set('auth', {'user': name, 'password': password});
                    resolve();
                    page('/');
                    this.registrationView.notifySuccess('Welcome aboard!');
                }).catch(response => {
                    reject(response.description);
                });
            }).catch(response => {
                reject(response.description);
            });
        });
    }

    showUserRoute(user) {
        if (api.isLoggedIn() && user == api.userName) {
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
