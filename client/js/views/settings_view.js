'use strict';

const views = require('../util/views.js');

class SettingsView {
    constructor() {
        this.template = views.getTemplate('settings');
    }

    render(ctx) {
        const target = document.getElementById('content-holder');
        const source = this.template({browsingSettings: ctx.getSettings()});

        const form = source.querySelector('form');
        views.decorateValidator(form);

        form.addEventListener('submit', e => {
            e.preventDefault();
            views.clearMessages(source);
            ctx.saveSettings({
                endlessScroll: form.querySelector('#endless-scroll').checked,
            });
        });

        views.listenToMessages(target);
        views.showView(target, source);
    }
}

module.exports = SettingsView;
