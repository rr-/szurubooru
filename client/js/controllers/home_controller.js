'use strict';

const page = require('page');
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
        page('*', (ctx, next) => { this._notFoundRoute(); });
    }

    _indexRoute() {
        topNavController.activate('home');
        this._homeView.render({});
    }

    _notFoundRoute() {
        topNavController.activate('');
        this._notFoundView.render({});
    }
}

module.exports = new HomeController();
