'use strict';

const topNavController = require('../controllers/top_nav_controller.js');
const HomeView = require('../views/home_view.js');

class HomeController {
    constructor() {
        this.homeView = new HomeView();
    }

    indexRoute() {
        topNavController.activate('home');
        this.homeView.render();
    }

    notFoundRoute() {
        topNavController.activate('');
    }
}

module.exports = new HomeController();
