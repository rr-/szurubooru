'use strict';

const events = require('../events.js');
const settings = require('../settings.js');
const EndlessPageView = require('../views/endless_page_view.js');
const ManualPageView = require('../views/manual_page_view.js');

class PageController {
    constructor() {
        events.listen(events.SettingsChange, () => {
            this._update();
            return true;
        });
        this._update();
    }

    _update() {
        if (settings.getSettings().endlessScroll) {
            this._pageView = new EndlessPageView();
        } else {
            this._pageView = new ManualPageView();
        }
    }

    run(ctx) {
        this._pageView.unrender();
        this._pageView.render(ctx);
    }

    stop() {
        this._pageView.unrender();
    }
}

module.exports = new PageController();
