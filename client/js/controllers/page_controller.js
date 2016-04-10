'use strict';

const api = require('../api.js');
const ManualPageView = require('../views/manual_page_view.js');

class PageController {
    constructor() {
        this.pageView = new ManualPageView();
    }

    run(ctx) {
        this.pageView.render(ctx);
    }
}

module.exports = new PageController();
