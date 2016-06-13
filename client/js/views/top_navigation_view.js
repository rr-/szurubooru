'use strict';

const views = require('../util/views.js');

class TopNavigationView {
    constructor() {
        this._template = views.getTemplate('top-navigation');
        this._navHolder = document.getElementById('top-navigation-holder');
        this._lastCtx = null;
    }

    render(ctx) {
        this._lastCtx = ctx;
        const target = this._navHolder;
        const source = this._template(ctx);
        views.showView(this._navHolder, source);
    }

    activate(key) {
        const allItemNodes = document.querySelectorAll(
            '#top-navigation-holder [data-name]');
        for (let itemNode of allItemNodes) {
            itemNode.classList.toggle(
                'active', itemNode.getAttribute('data-name') === key);
        }
    }
}

module.exports = TopNavigationView;
