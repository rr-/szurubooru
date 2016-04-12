'use strict';

const events = require('../events.js');
const settingsController = require('./settings_controller.js');
const EndlessPageView = require('../views/endless_page_view.js');
const ManualPageView = require('../views/manual_page_view.js');

class PageController {
    constructor() {
        events.listen(events.SettingsChange, () => {
            this.update();
        });
        this.update();
    }

    update() {
        if (settingsController.getSettings().endlessScroll) {
            this.pageView = new EndlessPageView();
        } else {
            this.pageView = new ManualPageView();
        }
    }

    run(ctx) {
        this.pageView.render(ctx);
    }

    stop() {
        this.pageView.unrender();
    }
}

module.exports = new PageController();
