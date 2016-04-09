'use strict';

const config = require('../config.js');
const views = require('../util/views.js');

class HelpView {
    constructor() {
        this.template = views.getTemplate('help');
        this.sectionTemplates = {};
        const sectionKeys = ['about', 'keyboard', 'search', 'comments', 'tos'];
        for (let section of sectionKeys) {
            const templateName = 'help-' + section;
            this.sectionTemplates[section] = views.getTemplate(templateName);
        }
    }

    render(ctx) {
        const target = document.getElementById('content-holder');
        const source = this.template();

        ctx.section = ctx.section || 'about';
        if (ctx.section in this.sectionTemplates) {
            views.showView(
                source.querySelector('.content'),
                this.sectionTemplates[ctx.section]({
                    name: config.name,
                }));
        }

        const allItemsSelector = '[data-name]';
        for (let item of source.querySelectorAll(allItemsSelector)) {
            if (item.getAttribute('data-name') === ctx.section) {
                item.className = 'active';
            } else {
                item.className = '';
            }
        }

        views.listenToMessages(target);
        views.showView(target, source);
    }
}

module.exports = HelpView;
