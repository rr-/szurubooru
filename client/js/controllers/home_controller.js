'use strict';

const page = require('page');
const topNavController = require('../controllers/top_nav_controller.js');
const HomeView = require('../views/home_view.js');

class HomeController {
    constructor() {
        this._homeView = new HomeView();
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
    }
}

module.exports = new HomeController();
