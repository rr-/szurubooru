'use strict';

const views = require('../util/views.js');

class PostsPageView {
    constructor() {
        this._template = views.getTemplate('posts-page');
    }

    render(ctx) {
        const target = ctx.target;
        const source = this._template(ctx);
        views.showView(target, source);
    }
}

module.exports = PostsPageView;
