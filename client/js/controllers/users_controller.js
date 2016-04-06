'use strict';

const cookies = require('js-cookie');
const page = require('page');
const api = require('../api.js');
const topNavController = require('../controllers/top_nav_controller.js');
const RegistrationView = require('../views/registration_view.js');
const UserView = require('../views/user_view.js');

class UsersController {
    constructor() {
        this.registrationView = new RegistrationView();
        this.userView = new UserView();
    }

    registerRoutes() {
        page('/register', () => { this.createUserRoute(); });
        page('/users', () => { this.listUsersRoute(); });
        page(
            '/user/:name',
            (ctx, next) => { this.showUserRoute(ctx.params.name); });
        page(
            '/user/:name/edit',
            (ctx, next) => { this.editUserRoute(ctx.params.name); });
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

    showUserRoute(name) {
        if (api.isLoggedIn() && name == api.userName) {
            topNavController.activate('account');
        } else {
            topNavController.activate('users');
        }
        this.userView.empty();
        api.get('/user/' + name).then(response => {
            this.userView.render({user: response.user});
        }).catch(response => {
            this.userView.notifyError(response.description);
        });
    }

    editUserRoute(user) {
        topNavController.activate('users');
    }
}

module.exports = new UsersController();
