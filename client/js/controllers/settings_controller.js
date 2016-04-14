'use strict';

const page = require('page');
const settings = require('../settings.js');
const topNavController = require('../controllers/top_nav_controller.js');
const SettingsView = require('../views/settings_view.js');

class SettingsController {
    constructor() {
        this.settingsView = new SettingsView();
    }

    registerRoutes() {
        page('/settings', (ctx, next) => { this.settingsRoute(); });
    }

    settingsRoute() {
        topNavController.activate('settings');
        this.settingsView.render({
            getSettings: () => settings.getSettings(),
            saveSettings: newSettings => settings.saveSettings(newSettings),
        });
    }
};

module.exports = new SettingsController();
