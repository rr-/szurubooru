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
        this.subsectionTemplates = {
            'search': {
                'default': views.getTemplate('help-search-general'),
                'posts': views.getTemplate('help-search-posts'),
                'users': views.getTemplate('help-search-users'),
                'tags': views.getTemplate('help-search-tags'),
            }
        };
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

        ctx.subsection = ctx.subsection || 'default';
        if (ctx.section in this.subsectionTemplates &&
                ctx.subsection in this.subsectionTemplates[ctx.section]) {
            views.showView(
                source.querySelector('.subcontent'),
                this.subsectionTemplates[ctx.section][ctx.subsection]({
                    name: config.name,
                }));
        }

        for (let item of source.querySelectorAll('.primary [data-name]')) {
            if (item.getAttribute('data-name') === ctx.section) {
                item.className = 'active';
            } else {
                item.className = '';
            }
        }

        for (let item of source.querySelectorAll('.secondary [data-name]')) {
            if (item.getAttribute('data-name') === ctx.subsection) {
                item.className = 'active';
            } else {
                item.className = '';
            }
        }

        views.listenToMessages(source);
        views.showView(target, source);

        views.scrollToHash();
    }
}

module.exports = HelpView;
