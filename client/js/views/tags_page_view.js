'use strict';

const views = require('../util/views.js');

class TagsPageView {
    constructor() {
        this.template = views.getTemplate('tags-page');
    }

    render(ctx) {
        const target = ctx.target;
        const source = this.template(ctx);
        views.showView(target, source);
    }
}

module.exports = TagsPageView;
