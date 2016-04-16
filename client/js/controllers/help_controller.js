'use strict';

const page = require('page');
const topNavController = require('../controllers/top_nav_controller.js');
const HelpView = require('../views/help_view.js');

class HelpController {
    constructor() {
        this.helpView = new HelpView();
    }

    registerRoutes() {
        page('/help', () => { this.showHelpRoute(); });
        page(
            '/help/:section',
            (ctx, next) => { this.showHelpRoute(ctx.params.section); });
        page(
            '/help/:section/:subsection',
            (ctx, next) => {
                this.showHelpRoute(ctx.params.section, ctx.params.subsection);
            });
    }

    showHelpRoute(section, subsection) {
        topNavController.activate('help');
        this.helpView.render({
            section: section,
            subsection: subsection,
        });
    }
}

module.exports = new HelpController();
