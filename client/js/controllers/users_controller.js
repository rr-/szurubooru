'use strict';

const page = require('page');
const api = require('../api.js');
const config = require('../config.js');
const events = require('../events.js');
const misc = require('../util/misc.js');
const views = require('../util/views.js');
const topNavController = require('../controllers/top_nav_controller.js');
const pageController = require('../controllers/page_controller.js');
const RegistrationView = require('../views/registration_view.js');
const UserView = require('../views/user_view.js');
const UsersHeaderView = require('../views/users_header_view.js');
const UsersPageView = require('../views/users_page_view.js');
const EmptyView = require('../views/empty_view.js');

const rankNames = new Map([
    ['anonymous', 'Anonymous'],
    ['restricted', 'Restricted user'],
    ['regular', 'Regular user'],
    ['power', 'Power user'],
    ['moderator', 'Moderator'],
    ['administrator', 'Administrator'],
    ['nobody', 'Nobody'],
]);

class UsersController {
    constructor() {
        this._registrationView = new RegistrationView();
        this._userView = new UserView();
        this._usersHeaderView = new UsersHeaderView();
        this._usersPageView = new UsersPageView();
        this._emptyView = new EmptyView();
    }

    registerRoutes() {
        page('/register', () => { this._createUserRoute(); });
        page(
            '/users/:query?',
            (ctx, next) => { misc.parseSearchQueryRoute(ctx, next); },
            (ctx, next) => { this._listUsersRoute(ctx, next); });
        page(
            '/user/:name',
            (ctx, next) => { this._loadUserRoute(ctx, next); },
            (ctx, next) => { this._showUserRoute(ctx, next); });
        page(
            '/user/:name/edit',
            (ctx, next) => { this._loadUserRoute(ctx, next); },
            (ctx, next) => { this._editUserRoute(ctx, next); });
        page(
            '/user/:name/delete',
            (ctx, next) => { this._loadUserRoute(ctx, next); },
            (ctx, next) => { this._deleteUserRoute(ctx, next); });
        page.exit(/\/users\/.*/, (ctx, next) => {
            pageController.stop();
            next();
        });
        page.exit(/\/user\/.*/, (ctx, next) => {
            this._cachedUser = null;
            next();
        });
    }

    _listUsersRoute(ctx, next) {
        topNavController.activate('users');

        pageController.run({
            state: ctx.state,
            requestPage: page => {
                const text = ctx.searchQuery.text;
                return api.get(
                    `/users/?query=${text}&page=${page}&pageSize=30`);
            },
            clientUrl: '/users/' + misc.formatSearchQuery({
                text: ctx.searchQuery.text, page: '{page}'}),
            searchQuery: ctx.searchQuery,
            headerRenderer: this._usersHeaderView,
            pageRenderer: this._usersPageView,
        });
    }

    _createUserRoute() {
        topNavController.activate('register');
        this._registrationView.render({
            register: (...args) => {
                return this._register(...args);
            }});
    }

    _loadUserRoute(ctx, next) {
        if (ctx.state.user) {
            next();
        } else if (this._cachedUser && this._cachedUser == ctx.params.name) {
            ctx.state.user = this._cachedUser;
            next();
        } else {
            api.get('/user/' + ctx.params.name).then(response => {
                response.rankName = rankNames.get(response.rank);
                ctx.state.user = response;
                ctx.save();
                this._cachedUser = response;
                next();
            }, response => {
                this._emptyView.render();
                events.notify(events.Error, response.description);
            });
        }
    }

    _showUserRoute(ctx, next) {
        this._show(ctx.state.user, 'summary');
    }

    _editUserRoute(ctx, next) {
        this._show(ctx.state.user, 'edit');
    }

    _deleteUserRoute(ctx, next) {
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
                api.forget();
                return api.login(name, password, false);
            }, response => {
                return Promise.reject(response.description);
            }).then(() => {
                resolve();
                page('/');
                events.notify(events.Success, 'Welcome aboard!');
            }, errorMessage => {
                reject();
                events.notify(events.Error, errorMessage);
            });
        });
    }

    _edit(user, data) {
        const isLoggedIn = api.isLoggedIn(user);
        const infix = isLoggedIn ? 'self' : 'any';
        let files = [];

        if (!data.name) {
            delete data.name;
        }
        if (!data.password) {
            delete data.password;
        }
        if (!api.hasPrivilege('users:edit:' + infix + ':email')) {
            delete data.email;
        }
        if (!data.rank) {
            delete data.rank;
        }
        if (!data.avatarStyle ||
                (data.avatarStyle == user.avatarStyle && !data.avatarContent)) {
            delete data.avatarStyle;
        }
        if (data.avatarContent) {
            files.avatar = data.avatarContent;
        }

        return new Promise((resolve, reject) => {
            api.put('/user/' + user.name, data, files)
                .then(response => {
                    this._cachedUser = response;
                    return isLoggedIn ?
                        api.login(
                            data.name || api.userName,
                            data.password || api.userPassword,
                            false) :
                        Promise.resolve();
                }, response => {
                    return Promise.reject(response.description);
                }).then(() => {
                    resolve();
                    if (data.name && data.name !== user.name) {
                        page('/user/' + data.name + '/edit');
                    }
                    events.notify(events.Success, 'Settings updated.');
                }, errorMessage => {
                    reject();
                    events.notify(events.Error, errorMessage);
                });
        });
    }

    _delete(user) {
        const isLoggedIn = api.isLoggedIn(user);
        return api.delete('/user/' + user.name)
            .then(response => {
                if (isLoggedIn) {
                    api.forget();
                    api.logout();
                }
                if (api.hasPrivilege('users:list')) {
                    page('/users');
                } else {
                    page('/');
                }
                events.notify(events.Success, 'Account deleted.');
                return Promise.resolve();
            }, response => {
                events.notify(events.Error, response.description);
                return Promise.reject();
            });
    }

    _show(user, section) {
        const isLoggedIn = api.isLoggedIn(user);
        const infix = isLoggedIn ? 'self' : 'any';

        const myRankIdx = api.user ? api.allRanks.indexOf(api.user.rank) : 0;
        let ranks = {};
        for (let [rankIdx, rankIdentifier] of api.allRanks.entries()) {
            if (rankIdentifier === 'anonymous') {
                continue;
            }
            if (rankIdx > myRankIdx) {
                continue;
            }
            ranks[rankIdentifier] = rankNames.get(rankIdentifier);
        }

        if (isLoggedIn) {
            topNavController.activate('account');
        } else {
            topNavController.activate('users');
        }
        this._userView.render({
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
