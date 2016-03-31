'use strict';

class HomeController {
    constructor(topNavigationController, homeView) {
        this.topNavigationController = topNavigationController;
        this.homeView = homeView;
    }

    indexRoute() {
        this.topNavigationController.activate('home');
        this.homeView.render();
    }

    notFoundRoute() {
        this.topNavigationController.activate('');
    }
}

module.exports = HomeController;
