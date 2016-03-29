'use strict';

class HelpController {
    constructor(topNavigationController, helpView) {
        this.topNavigationController = topNavigationController;
        this.helpView = helpView;
    }

    showHelpRoute(section) {
        this.topNavigationController.activate('help');
        this.helpView.render(section);
    }
}

module.exports = HelpController;
