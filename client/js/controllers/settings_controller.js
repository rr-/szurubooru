"use strict";

const settings = require("../models/settings.js");
const topNavigation = require("../models/top_navigation.js");
const SettingsView = require("../views/settings_view.js");

class SettingsController {
    constructor() {
        topNavigation.activate("settings");
        topNavigation.setTitle("Browsing settings");
        this._view = new SettingsView({
            settings: settings.get(),
        });
        this._view.addEventListener("submit", (e) => this._evtSubmit(e));
    }

    _evtSubmit(e) {
        this._view.clearMessages();
        settings.save(e.detail);
        this._view.showSuccess("Settings saved.");
    }
}

module.exports = (router) => {
    router.enter(["settings"], (ctx, next) => {
        ctx.controller = new SettingsController();
    });
};
