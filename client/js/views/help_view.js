'use strict';

const config = require('../config.js');
const views = require('../util/views.js');

class HelpView {
    constructor() {
        this._template = views.getTemplate('help');
        this._sectionTemplates = {};
        const sectionKeys = ['about', 'keyboard', 'search', 'comments', 'tos'];
        for (let section of sectionKeys) {
            const templateName = 'help-' + section;
            this._sectionTemplates[section] = views.getTemplate(templateName);
        }
        this._subsectionTemplates = {
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
        const source = this._template();

        ctx.section = ctx.section || 'about';
        if (ctx.section in this._sectionTemplates) {
            views.showView(
                source.querySelector('.content'),
                this._sectionTemplates[ctx.section]({
                    name: config.name,
                }));
        }

        ctx.subsection = ctx.subsection || 'default';
        if (ctx.section in this._subsectionTemplates &&
                ctx.subsection in this._subsectionTemplates[ctx.section]) {
            views.showView(
                source.querySelector('.subcontent'),
                this._subsectionTemplates[ctx.section][ctx.subsection]({
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
