'use strict';

const BaseView = require('./base_view.js');

class TopNavigationView extends BaseView {
    constructor(handlebars) {
        super(handlebars);
        this.template = this.getTemplate('top-navigation-template');
        this.navHolder = document.getElementById('top-nav-holder');
    }

    render(items) {
        this.navHolder.innerHTML = this.template({items: items});
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

module.exports = TopNavigationView;
