'use strict';

require('../util/handlebars-helpers.js');
const views = require('../util/views.js');

class TopNavView {
    constructor() {
        this.template = views.getTemplate('top-nav');
        this.navHolder = document.getElementById('top-nav-holder');
    }

    render(ctx) {
        const target = this.navHolder;
        const source = this.template(ctx);

        for (let link of source.querySelectorAll('a')) {
            const regex = new RegExp(
                '(' + link.getAttribute('accesskey') + ')', 'i');
            link.innerHTML = link.textContent.replace(
                regex,
                '<span class="access-key" data-accesskey="$1">$1</span>');
        }

        views.showView(this.navHolder, source);
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
