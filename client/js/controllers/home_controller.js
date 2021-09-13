"use strict";

const api = require("../api.js");
const config = require("../config.js");
const Info = require("../models/info.js");
const topNavigation = require("../models/top_navigation.js");
const HomeView = require("../views/home_view.js");

class HomeController {
    constructor() {
        topNavigation.activate("home");
        topNavigation.setTitle("Home");

        this._homeView = new HomeView({
            name: api.getName(),
            version: config.meta.version,
            buildDate: config.meta.buildDate,
            canListSnapshots: api.hasPrivilege("snapshots:list"),
            canListPosts: api.hasPrivilege("posts:list"),
            isDevelopmentMode: config.environment == "development",
        });

        Info.get().then(
            (info) => {
                this._homeView.setStats({
                    diskUsage: info.diskUsage,
                    postCount: info.postCount,
                });
                this._homeView.setFeaturedPost({
                    featuredPost: info.featuredPost,
                    featuringUser: info.featuringUser,
                    featuringTime: info.featuringTime,
                });
            },
            (error) => this._homeView.showError(error.message)
        );
    }

    showSuccess(message) {
        this._homeView.showSuccess(message);
    }

    showError(message) {
        this._homeView.showError(message);
    }
}

module.exports = (router) => {
    router.enter([], (ctx, next) => {
        ctx.controller = new HomeController();
    });
};
