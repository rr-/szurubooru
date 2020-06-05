"use strict";

const router = require("../router.js");
const api = require("../api.js");
const tags = require("../tags.js");
const pools = require("../pools.js");
const uri = require("../util/uri.js");
const topNavigation = require("../models/top_navigation.js");
const LoginView = require("../views/login_view.js");

class LoginController {
    constructor() {
        api.forget();
        topNavigation.activate("login");
        topNavigation.setTitle("Login");

        this._loginView = new LoginView();
        this._loginView.addEventListener("submit", (e) => this._evtLogin(e));
    }

    _evtLogin(e) {
        this._loginView.clearMessages();
        this._loginView.disableForm();
        api.forget();
        api.login(e.detail.name, e.detail.password, e.detail.remember).then(
            () => {
                const ctx = router.show(uri.formatClientLink());
                ctx.controller.showSuccess("Logged in");
                // reload tag category color map, this is required when `tag_categories:list` has a permission other than anonymous
                tags.refreshCategoryColorMap();
                pools.refreshCategoryColorMap();
            },
            (error) => {
                this._loginView.showError(error.message);
                this._loginView.enableForm();
            }
        );
    }
}

class LogoutController {
    constructor() {
        api.forget();
        api.logout();
        const ctx = router.show(uri.formatClientLink());
        ctx.controller.showSuccess("Logged out");
    }
}

module.exports = (router) => {
    router.enter(["login"], (ctx, next) => {
        ctx.controller = new LoginController();
    });
    router.enter(["logout"], (ctx, next) => {
        ctx.controller = new LogoutController();
    });
};
