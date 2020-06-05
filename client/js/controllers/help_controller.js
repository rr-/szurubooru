"use strict";

const topNavigation = require("../models/top_navigation.js");
const HelpView = require("../views/help_view.js");

class HelpController {
    constructor(section, subsection) {
        topNavigation.activate("help");
        topNavigation.setTitle("Help");
        this._helpView = new HelpView(section, subsection);
    }
}

module.exports = (router) => {
    router.enter(["help"], (ctx, next) => {
        new HelpController();
    });
    router.enter(["help", ":section"], (ctx, next) => {
        new HelpController(ctx.parameters.section);
    });
    router.enter(["help", ":section", ":subsection"], (ctx, next) => {
        new HelpController(ctx.parameters.section, ctx.parameters.subsection);
    });
};
