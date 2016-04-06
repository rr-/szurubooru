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

    render(section) {
        if (!section) {
            section = 'about';
        }
        if (!(section in this.sectionTemplates)) {
            this.showView('');
            return;
        }

        const content = this.sectionTemplates[section]({
            name: config.name,
        });

        this.showView(this.template({'content': content}));

        const allItemsSelector = '#content-holder [data-name]';
        for (let item of document.querySelectorAll(allItemsSelector)) {
            if (item.getAttribute('data-name') === section) {
                item.className = 'active';
            } else {
                item.className = '';
            }
        }
    }
}

module.exports = HelpView;
