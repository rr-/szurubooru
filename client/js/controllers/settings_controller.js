'use strict';

const router = require('../router.js');
const settings = require('../settings.js');
const TopNavigation = require('../models/top_navigation.js');
const SettingsView = require('../views/settings_view.js');

class SettingsController {
    constructor() {
        this._settingsView = new SettingsView();
    }

    registerRoutes() {
        router.enter('/settings', (ctx, next) => { this._settingsRoute(); });
    }

    _settingsRoute() {
        TopNavigation.activate('settings');
        this._settingsView.render({
            getSettings: () => settings.getSettings(),
            saveSettings: newSettings => settings.saveSettings(newSettings),
        });
    }
};

module.exports = new SettingsController();
