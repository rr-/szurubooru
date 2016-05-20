'use strict';

const page = require('page');
const settings = require('../settings.js');
const topNavController = require('../controllers/top_nav_controller.js');
const SettingsView = require('../views/settings_view.js');

class SettingsController {
    constructor() {
        this._settingsView = new SettingsView();
    }

    registerRoutes() {
        page('/settings', (ctx, next) => { this._settingsRoute(); });
    }

    _settingsRoute() {
        topNavController.activate('settings');
        this._settingsView.render({
            getSettings: () => settings.getSettings(),
            saveSettings: newSettings => settings.saveSettings(newSettings),
        });
    }
};

module.exports = new SettingsController();
