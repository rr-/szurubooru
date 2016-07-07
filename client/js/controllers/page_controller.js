'use strict';

const settings = require('../models/settings.js');
const EndlessPageView = require('../views/endless_page_view.js');
const ManualPageView = require('../views/manual_page_view.js');

class PageController {
    constructor(ctx) {
        const extendedContext = {
            getClientUrlForPage: ctx.getClientUrlForPage,
            parameters: ctx.parameters,
        };

        ctx.headerContext = Object.assign({}, extendedContext);
        ctx.pageContext = Object.assign({}, extendedContext);

        if (settings.get().endlessScroll) {
            this._view = new EndlessPageView(ctx);
        } else {
            this._view = new ManualPageView(ctx);
        }
    }

    showSuccess(message) {
        this._view.showSuccess(message);
    }

    showError(message) {
        this._view.showError(message);
    }
}

module.exports = PageController;
