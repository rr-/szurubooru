"use strict";

const views = require("../util/views.js");

const template = views.getTemplate("users-page");

class UsersPageView {
    constructor(ctx) {
        views.replaceContent(ctx.hostNode, template(ctx));
    }
}

module.exports = UsersPageView;
