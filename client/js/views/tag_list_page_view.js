'use strict';

const views = require('../util/views.js');

class TagListPageView {
    constructor() {
        this.template = views.getTemplate('tag-list-page');
    }

    render(ctx) {
        const target = ctx.target;
        const source = this.template(ctx);
        views.showView(target, source);
    }
}

module.exports = TagListPageView;
