'use strict';

const page = require('page');
const topNavController = require('../controllers/top_nav_controller.js');
const HelpView = require('../views/help_view.js');

class HelpController {
    constructor() {
        this._helpView = new HelpView();
    }

    registerRoutes() {
        page('/help', () => { this._showHelpRoute(); });
        page(
            '/help/:section',
            (ctx, next) => { this._showHelpRoute(ctx.params.section); });
        page(
            '/help/:section/:subsection',
            (ctx, next) => {
                this._showHelpRoute(ctx.params.section, ctx.params.subsection);
            });
    }

    _showHelpRoute(section, subsection) {
        topNavController.activate('help');
        this._helpView.render({
            section: section,
            subsection: subsection,
        });
    }
}

module.exports = new HelpController();
