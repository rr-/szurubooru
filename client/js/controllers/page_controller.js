'use strict';

const settings = require('../models/settings.js');
const EndlessPageView = require('../views/endless_page_view.js');
const ManualPageView = require('../views/manual_page_view.js');

class PageController {
    constructor(ctx) {
        const extendedContext = {
            clientUrl: ctx.clientUrl,
            searchQuery: ctx.searchQuery,
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

    static createHistoryCacheProxy(routerCtx, requestPage) {
        return page => {
            if (routerCtx.state.response) {
                return new Promise((resolve, reject) => {
                    resolve(routerCtx.state.response);
                });
            }
            const promise = requestPage(page);
            promise.then(response => {
                routerCtx.state.response = response;
                routerCtx.save();
            });
            return promise;
        };
    }
}

module.exports = PageController;
