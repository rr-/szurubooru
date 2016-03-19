'use strict';

class HomeController {
    constructor(topNavigationController) {
        this.topNavigationController = topNavigationController;
    }

    indexRoute() {
        this.topNavigationController.activate('home');
    }

    notFoundRoute() {
        this.topNavigationController.activate('');
    }
}

module.exports = HomeController;
