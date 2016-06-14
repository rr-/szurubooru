'use strict';

const router = require('../router.js');
const api = require('../api.js');
const config = require('../config.js');
const views = require('../util/views.js');
const topNavigation = require('../models/top_navigation.js');
const UserView = require('../views/user_view.js');
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

class UserController {
    constructor(ctx, section) {
        new Promise((resolve, reject) => {
            if (ctx.state.user) {
                resolve(ctx.state.user);
                return;
            }
            api.get('/user/' + ctx.params.name).then(response => {
                response.rankName = rankNames.get(response.rank);
                ctx.state.user = response;
                ctx.save();
                resolve(ctx.state.user);
            }, response => {
                reject(response.description);
            });
        }).then(user => {
            const isLoggedIn = api.isLoggedIn(user);
            const infix = isLoggedIn ? 'self' : 'any';

            const myRankIndex = api.user ?
                api.allRanks.indexOf(api.user.rank) :
                0;
            let ranks = {};
            for (let [rankIdx, rankIdentifier] of api.allRanks.entries()) {
                if (rankIdentifier === 'anonymous') {
                    continue;
                }
                if (rankIdx > myRankIndex) {
                    continue;
                }
                ranks[rankIdentifier] = rankNames.get(rankIdentifier);
            }

            if (isLoggedIn) {
                topNavigation.activate('account');
            } else {
                topNavigation.activate('users');
            }
            this._view = new UserView({
                user: user,
                section: section,
                isLoggedIn: isLoggedIn,
                canEditName: api.hasPrivilege(`users:edit:${infix}:name`),
                canEditPassword: api.hasPrivilege(`users:edit:${infix}:pass`),
                canEditEmail: api.hasPrivilege(`users:edit:${infix}:email`),
                canEditRank: api.hasPrivilege(`users:edit:${infix}:rank`),
                canEditAvatar: api.hasPrivilege(`users:edit:${infix}:avatar`),
                canEditAnything: api.hasPrivilege(`users:edit:${infix}`),
                canDelete: api.hasPrivilege(`users:delete:${infix}`),
                ranks: ranks,
            });
            this._view.addEventListener('change', e => this._evtChange(e));
            this._view.addEventListener('delete', e => this._evtDelete(e));
        }, errorMessage => {
            this._view = new EmptyView();
            this._view.showError(errorMessage);
        });
    }

    _evtChange(e) {
        this._view.clearMessages();
        this._view.disableForm();
        const isLoggedIn = api.isLoggedIn(e.detail.user);
        const infix = isLoggedIn ? 'self' : 'any';

        const files = [];
        const data = {};
        if (e.detail.name) {
            data.name = e.detail.name;
        }
        if (e.detail.password) {
            data.password = e.detail.password;
        }
        if (api.hasPrivilege('users:edit:' + infix + ':email')) {
            data.email = e.detail.email;
        }
        if (e.detail.rank) {
            data.rank = e.detail.rank;
        }
        if (e.detail.avatarStyle &&
                (e.detail.avatarStyle != e.detail.user.avatarStyle ||
                e.detail.avatarContent)) {
            data.avatarStyle = e.detail.avatarStyle;
        }
        if (e.detail.avatarContent) {
            files.avatar = e.detail.avatarContent;
        }

        api.put('/user/' + e.detail.user.name, data, files)
            .then(response => {
                return isLoggedIn ?
                    api.login(
                        data.name || api.userName,
                        data.password || api.userPassword,
                        false) :
                    Promise.resolve();
            }, response => {
                return Promise.reject(response.description);
            }).then(() => {
                if (data.name && data.name !== e.detail.user.name) {
                    // TODO: update header links and text
                    router.replace('/user/' + data.name + '/edit', null, false);
                }
                this._view.showSuccess('Settings updated.');
                this._view.enableForm();
            }, errorMessage => {
                this._view.showError(errorMessage);
                this._view.enableForm();
            });
    }

    _evtDelete(e) {
        this._view.clearMessages();
        this._view.disableForm();
        const isLoggedIn = api.isLoggedIn(e.detail.user);
        api.delete('/user/' + e.detail.user.name)
            .then(response => {
                if (isLoggedIn) {
                    api.forget();
                    api.logout();
                }
                if (api.hasPrivilege('users:list')) {
                    const ctx = router.show('/users');
                    ctx.controller.showSuccess('Account deleted.');
                } else {
                    const ctx = router.show('/');
                    ctx.controller.showSuccess('Account deleted.');
                }
            }, response => {
                this._view.showError(response.description);
                this._view.enableForm();
            });
    }
}

module.exports = router => {
    router.enter('/user/:name', (ctx, next) => {
        ctx.controller = new UserController(ctx, 'summary');
    });
    router.enter('/user/:name/edit', (ctx, next) => {
        ctx.controller = new UserController(ctx, 'edit');
    });
    router.enter('/user/:name/delete', (ctx, next) => {
        ctx.controller = new UserController(ctx, 'delete');
    });
};
