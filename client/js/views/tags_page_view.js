"use strict";

const views = require("../util/views.js");

const template = views.getTemplate("tags-page");

class TagsPageView {
    constructor(ctx) {
        views.replaceContent(ctx.hostNode, template(ctx));
    }
}

module.exports = TagsPageView;
