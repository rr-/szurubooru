'use strict';

const views = require('../util/views.js');

class TopNavView {
    constructor() {
        this._template = views.getTemplate('top-nav');
        this._navHolder = document.getElementById('top-nav-holder');
        this._lastCtx = null;
    }

    render(ctx) {
        this._lastCtx = ctx;
        const target = this._navHolder;
        const source = this._template(ctx);
        views.showView(this._navHolder, source);
    }

    activate(itemName) {
        const allItemsSelector = '#top-nav-holder [data-name]';
        const currentItemSelector =
            '#top-nav-holder [data-name="' + itemName + '"]';
        for (let item of document.querySelectorAll(allItemsSelector)) {
            item.className = '';
        }
        const currentItem = document.querySelectorAll(currentItemSelector);
        if (currentItem.length > 0) {
            currentItem[0].className = 'active';
        }
    }
}

module.exports = TopNavView;
