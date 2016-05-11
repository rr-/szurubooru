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

const rankNames = {
    anonymous: 'Anonymous',
    restricted: 'Restricted user',
    regular: 'Regular user',
    power: 'Power user',
    moderator: 'Moderator',
    administrator: 'Administrator',
    nobody: 'Nobody',
};

class UsersController {
    constructor() {
        this.registrationView = new RegistrationView();
        this.userView = new UserView();
        this.usersHeaderView = new UsersHeaderView();
        this.usersPageView = new UsersPageView();
        this.emptyView = new EmptyView();
    }

    registerRoutes() {
        page('/register', () => { this.createUserRoute(); });
        page(
            '/users/:query?',
            (ctx, next) => { misc.parseSearchQueryRoute(ctx, next); },
            (ctx, next) => { this.listUsersRoute(ctx, next); });
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
        page.exit(/\/users\/.*/, (ctx, next) => {
            pageController.stop();
            next();
        });
        page.exit(/\/user\/.*/, (ctx, next) => {
            this.user = null;
            next();
        });
    }

    listUsersRoute(ctx, next) {
        topNavController.activate('users');

        pageController.run({
            state: ctx.state,
            requestPage: page => {
                return api.get(
                    '/users/?query={text}&page={page}&pageSize=30'.format({
                        text: ctx.searchQuery.text,
                        page: page}));
            },
            clientUrl: '/users/' + misc.formatSearchQuery({
                text: ctx.searchQuery.text, page: '{page}'}),
            searchQuery: ctx.searchQuery,
            headerRenderer: this.usersHeaderView,
            pageRenderer: this.usersPageView,
        });
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
                response.user.rankName = rankNames[response.user.rank];
                ctx.state.user = response.user;
                ctx.save();
                this.user = response.user;
                next();
            }, response => {
                this.emptyView.render();
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
                    this.user = response.user;
                    return isLoggedIn ?
                        api.login(
                            data.name || api.userName,
                            data.password || api.userPassword,
                            false) :
                        Promise.fulfill();
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
        return new Promise((resolve, reject) => {
            api.delete('/user/' + user.name)
                .then(response => {
                    if (isLoggedIn) {
                        api.forget();
                        api.logout();
                    }
                    resolve();
                    if (api.hasPrivilege('users:list')) {
                        page('/users');
                    } else {
                        page('/');
                    }
                    events.notify(events.Success, 'Account deleted');
                }, response => {
                    reject();
                    events.notify(events.Error, response.description);
                });
        });
    }

    _show(user, section) {
        const isLoggedIn = api.isLoggedIn(user);
        const infix = isLoggedIn ? 'self' : 'any';

        const myRankIdx = api.user ? api.allRanks.indexOf(api.user.rank) : 0;
        let ranks = {};
        for (let rankIdx of misc.range(api.allRanks.length)) {
            const rankIdentifier = api.allRanks[rankIdx];
            if (rankIdentifier === 'anonymous') {
                continue;
            }
            if (rankIdx > myRankIdx) {
                continue;
            }
            ranks[rankIdentifier] = Object.values(rankNames)[rankIdx];
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
