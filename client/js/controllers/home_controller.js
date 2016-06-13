'use strict';

const router = require('../router.js');
const api = require('../api.js');
const events = require('../events.js');
const TopNavigation = require('../models/top_navigation.js');
const HomeView = require('../views/home_view.js');
const NotFoundView = require('../views/not_found_view.js');

class HomeController {
    constructor() {
        this._homeView = new HomeView();
        this._notFoundView = new NotFoundView();
    }

    registerRoutes() {
        router.enter(
            '/',
            (ctx, next) => { this._indexRoute(); });
        router.enter(
            '*',
            (ctx, next) => { this._notFoundRoute(ctx); });
    }

    _indexRoute() {
        TopNavigation.activate('home');

        api.get('/info')
            .then(response => {
                this._homeView.render({
                    canListPosts: api.hasPrivilege('posts:list'),
                    diskUsage: response.diskUsage,
                    postCount: response.postCount,
                    featuredPost: response.featuredPost,
                    featuringUser: response.featuringUser,
                    featuringTime: response.featuringTime,
                });
            },
            response => {
                this._homeView.render({
                    canListPosts: api.hasPrivilege('posts:list'),
                });
                events.notify(events.Error, response.description);
            });
    }

    _notFoundRoute(ctx) {
        TopNavigation.activate('');
        this._notFoundView.render({path: ctx.canonicalPath});
    }
}

module.exports = new HomeController();
