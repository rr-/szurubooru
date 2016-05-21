'use strict';

const config = require('../config.js');
const views = require('../util/views.js');

class NotFoundView {
    constructor() {
        this._template = views.getTemplate('not-found');
    }

    render(ctx) {
        const target = document.getElementById('content-holder');
        const source = this._template({});
        views.showView(target, source);
    }
}

module.exports = NotFoundView;
