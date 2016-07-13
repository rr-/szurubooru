'use strict';

const config = require('../config.js');
const views = require('../util/views.js');

const template = views.getTemplate('help');
const sectionTemplates = {
    'about': views.getTemplate('help-about'),
    'keyboard': views.getTemplate('help-keyboard'),
    'search': views.getTemplate('help-search'),
    'comments': views.getTemplate('help-comments'),
    'tos': views.getTemplate('help-tos'),
};
const subsectionTemplates = {
    'search': {
        'default': views.getTemplate('help-search-general'),
        'posts': views.getTemplate('help-search-posts'),
        'users': views.getTemplate('help-search-users'),
        'tags': views.getTemplate('help-search-tags'),
    },
};

class HelpView {
    constructor(section, subsection) {
        this._hostNode = document.getElementById('content-holder');

        const sourceNode = template();
        const ctx = {
            name: config.name,
        };

        section = section || 'about';
        if (section in sectionTemplates) {
            views.replaceContent(
                sourceNode.querySelector('.content'),
                sectionTemplates[section](ctx));
        }

        subsection = subsection || 'default';
        if (section in subsectionTemplates &&
                subsection in subsectionTemplates[section]) {
            views.replaceContent(
                sourceNode.querySelector('.subcontent'),
                subsectionTemplates[section][subsection](ctx));
        }

        for (let itemNode of
                sourceNode.querySelectorAll('.primary [data-name]')) {
            itemNode.classList.toggle(
                'active',
                itemNode.getAttribute('data-name') === section);
        }

        for (let itemNode of
                sourceNode.querySelectorAll('.secondary [data-name]')) {
            itemNode.classList.toggle(
                'active',
                itemNode.getAttribute('data-name') === subsection);
        }

        views.replaceContent(this._hostNode, sourceNode);
        views.syncScrollPosition();
    }
}

module.exports = HelpView;
