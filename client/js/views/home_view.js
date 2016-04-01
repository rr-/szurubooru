'use strict';

const util = require('../util.js');
const config = require('../config.js');
const BaseView = require('./base_view.js');

class HomeView extends BaseView {
    constructor() {
        super();
        this.template = this.getTemplate('home-template');
    }

    render(section) {
        this.showView(this.template({
            name: config.basic.name,
            version: config.meta.version,
            buildDate: util.formatRelativeTime(config.meta.buildDate),
        }));
    }
}

module.exports = HomeView;
