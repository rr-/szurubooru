'use strict';

const api = require('../api.js');
const config = require('../config.js');
const topNavigation = require('../models/top_navigation.js');
const HomeView = require('../views/home_view.js');

class HomeController {
    constructor() {
        topNavigation.activate('home');

        this._homeView = new HomeView({
            name: config.name,
            version: config.meta.version,
            buildDate: config.meta.buildDate,
            canListPosts: api.hasPrivilege('posts:list'),
        });

        api.get('/info')
            .then(response => {
                this._homeView.setStats({
                    diskUsage: response.diskUsage,
                    postCount: response.postCount,
                });
                this._homeView.setFeaturedPost({
                    featuredPost: response.featuredPost,
                    featuringUser: response.featuringUser,
                    featuringTime: response.featuringTime,
                });
            },
            response => {
                this._homeView.showError(response.description);
            });
    }

    showSuccess(message) {
        this._homeView.showSuccess(message);
    }

    showError(message) {
        this._homeView.showError(message);
    }
};

module.exports = router => {
    router.enter('/', (ctx, next) => {
        ctx.controller = new HomeController();
    });
};
