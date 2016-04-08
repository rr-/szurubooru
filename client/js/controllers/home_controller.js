'use strict';

const page = require('page');
const topNavController = require('../controllers/top_nav_controller.js');
const HomeView = require('../views/home_view.js');

class HomeController {
    constructor() {
        this.homeView = new HomeView();
    }

    registerRoutes() {
        page('/', (ctx, next) => { this.indexRoute(); });
        page('*', (ctx, next) => { this.notFoundRoute(); });
    }

    indexRoute() {
        topNavController.activate('home');
        this.homeView.render({});
    }

    notFoundRoute() {
        topNavController.activate('');
    }
}

module.exports = new HomeController();
