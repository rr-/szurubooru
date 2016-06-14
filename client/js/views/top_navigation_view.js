'use strict';

const views = require('../util/views.js');

const template = views.getTemplate('top-navigation');

class TopNavigationView {
    constructor() {
        this._hostNode = document.getElementById('top-navigation-holder');
    }

    render(ctx) {
        views.replaceContent(this._hostNode, template(ctx));
    }

    activate(key) {
        for (let itemNode of this._hostNode.querySelectorAll('[data-name]')) {
            itemNode.classList.toggle(
                'active', itemNode.getAttribute('data-name') === key);
        }
    }
}

module.exports = TopNavigationView;
