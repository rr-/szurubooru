'use strict';

const BaseView = require('./base_view.js');

class TopNavView extends BaseView {
    constructor() {
        super();
        this.template = this.getTemplate('top-nav-template');
        this.navHolder = document.getElementById('top-nav-holder');
    }

    render(items) {
        this.navHolder.innerHTML = this.template({items: items});
        for (let link of this.navHolder.querySelectorAll('a')) {
            const regex = new RegExp(
                '(' + link.getAttribute('accesskey') + ')', 'i');
            link.innerHTML = link.textContent.replace(
                regex,
                '<span class="access-key" data-accesskey="$1">$1</span>');
        }
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
