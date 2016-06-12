'use strict';

const router = require('../router.js');
const topNavController = require('../controllers/top_nav_controller.js');
const HelpView = require('../views/help_view.js');

class HelpController {
    constructor() {
        this._helpView = new HelpView();
    }

    registerRoutes() {
        router.enter(
            '/help',
            (ctx, next) => { this._showHelpRoute(); });
        router.enter(
            '/help/:section',
            (ctx, next) => { this._showHelpRoute(ctx.params.section); });
        router.enter(
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
