'use strict';

const page = require('page');
const api = require('../api.js');
const events = require('../events.js');
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
            (ctx, next) => { this.loadUserRoute(ctx, next); },
            (ctx, next) => { this.showUserRoute(ctx, next); });
        page(
            '/user/:name/edit',
            (ctx, next) => { this.loadUserRoute(ctx, next); },
            (ctx, next) => { this.editUserRoute(ctx, next); });
        page.exit('/user/', (ctx, next) => { this.user = null; });
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
            name: name,
            password: password,
            email: email
        };
        return new Promise((resolve, reject) => {
            api.post('/users/', data).then(() => {
                api.login(name, password, false).then(() => {
                    resolve();
                    page('/');
                    events.notify(events.Success, 'Welcome aboard!');
                }).catch(response => {
                    reject(response.description);
                });
            }).catch(response => {
                reject(response.description);
            });
        });
    }

    loadUserRoute(ctx, next) {
        if (ctx.state.user) {
            next();
        } else if (this.user && this.user.name == ctx.params.name) {
            ctx.state.user = this.user;
            next();
        } else {
            api.get('/user/' + ctx.params.name).then(response => {
                ctx.state.user = response.user;
                ctx.save();
                this.user = response.user;
                next();
            }).catch(response => {
                this.userView.empty();
                events.notify(events.Error, response.description);
            });
        }
    }

    _show(user, section) {
        const isPrivate = api.isLoggedIn() && user.name == api.userName;
        if (isPrivate) {
            topNavController.activate('account');
        } else {
            topNavController.activate('users');
        }
        this.userView.render({
            user: user, section: section, isPrivate: isPrivate});
    }

    showUserRoute(ctx, next) {
        this._show(ctx.state.user, 'summary');
    }

    editUserRoute(ctx, next) {
        this._show(ctx.state.user, 'edit');
    }
}

module.exports = new UsersController();
