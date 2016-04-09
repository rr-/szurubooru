'use strict';

const page = require('page');
const api = require('../api.js');
const config = require('../config.js');
const events = require('../events.js');
const misc = require('../util/misc.js');
const views = require('../util/views.js');
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
        page(
            '/user/:name/delete',
            (ctx, next) => { this.loadUserRoute(ctx, next); },
            (ctx, next) => { this.deleteUserRoute(ctx, next); });
        page.exit('/user/', (ctx, next) => { this.user = null; });
    }

    listUsersRoute() {
        topNavController.activate('users');
    }

    createUserRoute() {
        topNavController.activate('register');
        this.registrationView.render({
            register: (...args) => {
                return this._register(...args);
            }});
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
                views.emptyView(document.getElementById('content-holder'));
                events.notify(events.Error, response.description);
            });
        }
    }

    showUserRoute(ctx, next) {
        this._show(ctx.state.user, 'summary');
    }

    editUserRoute(ctx, next) {
        this._show(ctx.state.user, 'edit');
    }

    deleteUserRoute(ctx, next) {
        this._show(ctx.state.user, 'delete');
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
                }).catch(errorMessage => {
                    reject();
                    events.notify(events.Error, errorMessage);
                });
            }).catch(response => {
                reject();
                events.notify(events.Error, response.description);
            });
        });
    }

    _edit(user, newName, newPassword, newEmail, newRank) {
        const data = {};
        if (newName) { data.name = newName; }
        if (newPassword) { data.password = newPassword; }
        if (newEmail) { data.email = newEmail; }
        if (newRank) { data.rank = newRank; }
        /* TODO: avatar */
        const isLoggedIn = api.isLoggedIn() && api.user.id == user.id;
        return new Promise((resolve, reject) => {
            api.put('/user/' + user.name, data)
                .then(response => {
                    const next = () => {
                        resolve();
                        page('/user/' + newName + '/edit');
                        events.notify(events.Success, 'Settings updated');
                    };
                    if (isLoggedIn) {
                        api.login(
                                newName,
                                newPassword || api.userPassword,
                                false)
                            .then(next)
                            .catch(errorMessage => {
                                reject();
                                events.notify(events.Error, errorMessage);
                            });
                    } else {
                        next();
                    }
                }).catch(response => {
                    reject();
                    events.notify(events.Error, response.description);
                });
        });
    }

    _delete(user) {
        const isLoggedIn = api.isLoggedIn() && api.user.id == user.id;
        return new Promise((resolve, reject) => {
            api.delete('/user/' + user.name)
                .then(response => {
                    if (isLoggedIn) {
                        api.logout();
                    }
                    resolve();
                    if (api.hasPrivilege('users:list')) {
                        page('/users');
                    } else {
                        page('/');
                    }
                    events.notify(events.Success, 'Account deleted');
                }).catch(response => {
                    reject();
                    events.notify(events.Error, response.description);
                });
        });
    }

    _show(user, section) {
        const isLoggedIn = api.isLoggedIn() && api.user.id == user.id;
        const infix = isLoggedIn ? 'self' : 'any';

        const myRankIdx = api.user ? config.ranks.indexOf(api.user.rank) : 0;
        const rankNames = Object.values(config.rankNames);
        let ranks = {};
        for (let rankIdx of misc.range(config.ranks.length)) {
            const rankIdentifier = config.ranks[rankIdx];
            if (rankIdentifier === 'anonymous') {
                continue;
            }
            if (rankIdx > myRankIdx) {
                continue;
            }
            ranks[rankIdentifier] = rankNames[rankIdx];
        }

        if (isLoggedIn) {
            topNavController.activate('account');
        } else {
            topNavController.activate('users');
        }
        this.userView.render({
            user: user,
            section: section,
            isLoggedIn: isLoggedIn,
            canEditName: api.hasPrivilege('users:edit:' + infix + ':name'),
            canEditPassword: api.hasPrivilege('users:edit:' + infix + ':pass'),
            canEditEmail: api.hasPrivilege('users:edit:' + infix + ':email'),
            canEditRank: api.hasPrivilege('users:edit:' + infix + ':rank'),
            canEditAvatar: api.hasPrivilege('users:edit:' + infix + ':avatar'),
            canEditAnything: api.hasPrivilege('users:edit:' + infix),
            canDelete: api.hasPrivilege('users:delete:' + infix),
            ranks: ranks,
            edit: (...args) => { return this._edit(user, ...args); },
            delete: (...args) => { return this._delete(user, ...args); },
        });
    }
}

module.exports = new UsersController();
