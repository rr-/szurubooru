"use strict";

const topNavigation = require("../models/top_navigation.js");
const NotFoundView = require("../views/not_found_view.js");

class NotFoundController {
    constructor(path) {
        topNavigation.activate("");
        topNavigation.setTitle("Not found");
        this._notFoundView = new NotFoundView(path);
    }
}

module.exports = (router) => {
    router.enter(null, (ctx, next) => {
        ctx.controller = new NotFoundController(ctx.canonicalPath);
    });
};
