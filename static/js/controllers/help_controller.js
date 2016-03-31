'use strict';

const topNavController = require('../controllers/top_nav_controller.js');
const HelpView = require('../views/help_view.js');

class HelpController {
    constructor() {
        this.helpView = new HelpView();
    }

    showHelpRoute(section) {
        topNavController.activate('help');
        this.helpView.render(section);
    }
}

module.exports = new HelpController();
