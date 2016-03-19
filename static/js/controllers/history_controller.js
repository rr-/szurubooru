'use strict';

class HistoryController {
    constructor(topNavigationController) {
        this.topNavigationController = topNavigationController;
    }

    listHistoryRoute() {
        this.topNavigationController.activate('');
    }
}

module.exports = HistoryController;
