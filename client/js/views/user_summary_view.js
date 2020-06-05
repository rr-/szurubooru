"use strict";

const views = require("../util/views.js");

const template = views.getTemplate("user-summary");

class UserSummaryView {
    constructor(ctx) {
        this._hostNode = ctx.hostNode;
        views.replaceContent(this._hostNode, template(ctx));
    }
}

module.exports = UserSummaryView;
