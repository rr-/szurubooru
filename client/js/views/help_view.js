'use strict';

const config = require('../config.js');
const BaseView = require('./base_view.js');

class HelpView extends BaseView {
    constructor() {
        super();
        this.template = this.getTemplate('help-template');
        this.sectionTemplates = {};
        const sectionKeys = ['about', 'keyboard', 'search', 'comments', 'tos'];
        for (let section of sectionKeys) {
            const templateName = 'help-' + section + '-template';
            this.sectionTemplates[section] = this.getTemplate(templateName);
        }
    }

    render(ctx) {
        const target = this.contentHolder;
        const source = this.template();

        ctx.section = ctx.section || 'about';
        if (!(ctx.section in this.sectionTemplates)) {
            this.emptyView(this.contentHolder);
            return;
        }

        this.showView(
            source.querySelector('.content'),
            this.sectionTemplates[ctx.section]({
                name: config.name,
            }));

        const allItemsSelector = '[data-name]';
        for (let item of source.querySelectorAll(allItemsSelector)) {
            if (item.getAttribute('data-name') === ctx.section) {
                item.className = 'active';
            } else {
                item.className = '';
            }
        }

        this.showView(target, source);
    }
}

module.exports = HelpView;
