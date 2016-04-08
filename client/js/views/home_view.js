'use strict';

const config = require('../config.js');
const BaseView = require('./base_view.js');

class HomeView extends BaseView {
    constructor() {
        super();
        this.template = this.getTemplate('home-template');
    }

    render(ctx) {
        const target = this.contentHolder;
        const source = this.template({
            name: config.name,
            version: config.meta.version,
            buildDate: config.meta.buildDate,
        });
        this.showView(target, source);
    }
}

module.exports = HomeView;
