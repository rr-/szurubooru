'use strict';

const events = require('../events.js');
const settings = require('../settings.js');
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
        if (settings.getSettings().endlessScroll) {
            this.pageView = new EndlessPageView();
        } else {
            this.pageView = new ManualPageView();
        }
    }

    run(ctx) {
        this.pageView.unrender();
        this.pageView.render(ctx);
    }

    stop() {
        this.pageView.unrender();
    }
}

module.exports = new PageController();
