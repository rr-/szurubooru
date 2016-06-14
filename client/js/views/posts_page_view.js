'use strict';

const views = require('../util/views.js');

const template = views.getTemplate('posts-page');

class PostsPageView {
    constructor(ctx) {
        views.replaceContent(ctx.hostNode, template(ctx));
    }
}

module.exports = PostsPageView;
