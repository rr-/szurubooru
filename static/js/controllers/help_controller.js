'use strict';

class HelpController {
    constructor(topNavigationController) {
        this.topNavigationController = topNavigationController;
    }

    showHelpRoute() {
        this.topNavigationController.activate('help');
    }
}

module.exports = HelpController;
