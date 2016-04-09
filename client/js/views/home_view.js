'use strict';

const config = require('../config.js');
const views = require('../util/views.js');

class HomeView {
    constructor() {
        this.template = views.getTemplate('home');
    }

    render(ctx) {
        const target = document.getElementById('content-holder');
        const source = this.template({
            name: config.name,
            version: config.meta.version,
            buildDate: config.meta.buildDate,
        });

        views.listenToMessages(target);
        views.showView(target, source);
    }
}

module.exports = HomeView;
