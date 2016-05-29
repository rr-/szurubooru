'use strict';

const page = require('page');
const api = require('../api.js');
const events = require('../events.js');
const topNavController = require('../controllers/top_nav_controller.js');
const HomeView = require('../views/home_view.js');
const NotFoundView = require('../views/not_found_view.js');

class HomeController {
    constructor() {
        this._homeView = new HomeView();
        this._notFoundView = new NotFoundView();
    }

    registerRoutes() {
        page('/', (ctx, next) => { this._indexRoute(); });
        page('*', (ctx, next) => { this._notFoundRoute(ctx); });
    }

    _indexRoute() {
        topNavController.activate('home');

        api.get('/info')
            .then(response => {
                this._homeView.render({
                    canListPosts: api.hasPrivilege('posts:list'),
                    canListComments: api.hasPrivilege('comments:list'),
                    canListTags: api.hasPrivilege('tags:list'),
                    canListUsers: api.hasPrivilege('users:list'),
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
                    canListComments: api.hasPrivilege('comments:list'),
                    canListTags: api.hasPrivilege('tags:list'),
                    canListUsers: api.hasPrivilege('users:list'),
                });
                events.notify(events.Error, response.description);
            });
    }

    _notFoundRoute(ctx) {
        topNavController.activate('');
        this._notFoundView.render({path: ctx.canonicalPath});
    }
}

module.exports = new HomeController();
