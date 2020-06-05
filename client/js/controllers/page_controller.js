"use strict";

const settings = require("../models/settings.js");
const EndlessPageView = require("../views/endless_page_view.js");
const ManualPageView = require("../views/manual_page_view.js");

class PageController {
    constructor(ctx) {
        if (settings.get().endlessScroll) {
            this._view = new EndlessPageView();
        } else {
            this._view = new ManualPageView();
        }
    }

    get view() {
        return this._view;
    }

    run(ctx) {
        this._view.run(ctx);
    }

    showSuccess(message) {
        this._view.showSuccess(message);
    }

    showError(message) {
        this._view.showError(message);
    }
}

module.exports = PageController;
