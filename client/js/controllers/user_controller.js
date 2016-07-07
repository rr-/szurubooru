'use strict';

const router = require('../router.js');
const api = require('../api.js');
const config = require('../config.js');
const views = require('../util/views.js');
const User = require('../models/user.js');
const topNavigation = require('../models/top_navigation.js');
const UserView = require('../views/user_view.js');
const EmptyView = require('../views/empty_view.js');

class UserController {
    constructor(ctx, section) {
        User.get(ctx.parameters.name).then(user => {
            const isLoggedIn = api.isLoggedIn(user);
            const infix = isLoggedIn ? 'self' : 'any';

            this._name = ctx.parameters.name;
            user.addEventListener('change', e => this._evtSaved(e));

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
                ranks[rankIdentifier] = api.rankNames.get(rankIdentifier);
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

    _evtSaved(e) {
        if (this._name !== e.detail.user.name) {
            router.replace(
                '/user/' + e.detail.user.name + '/edit', null, false);
        }
    }

    _evtChange(e) {
        this._view.clearMessages();
        this._view.disableForm();
        const isLoggedIn = api.isLoggedIn(e.detail.user);
        const infix = isLoggedIn ? 'self' : 'any';

        if (e.detail.name !== undefined) {
            e.detail.user.name = e.detail.name;
        }
        if (e.detail.email !== undefined) {
            e.detail.user.email = e.detail.email;
        }
        if (e.detail.rank !== undefined) {
            e.detail.user.rank = e.detail.rank;
        }

        if (e.detail.password !== undefined) {
            e.detail.user.password = e.detail.password;
        }

        if (e.detail.avatarStyle !== undefined) {
            e.detail.user.avatarStyle = e.detail.avatarStyle;
            if (e.detail.avatarContent) {
                e.detail.user.avatarContent = e.detail.avatarContent;
            }
        }

        e.detail.user.save().then(() => {
            return isLoggedIn ?
                api.login(
                    e.detail.name || api.userName,
                    e.detail.password || api.userPassword,
                    false) :
                Promise.resolve();
        }, errorMessage => {
            return Promise.reject(errorMessage);
        }).then(() => {
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
        e.detail.user.delete()
            .then(() => {
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
            }, errorMessage => {
                this._view.showError(errorMessage);
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
