'use strict';

const page = require('page');
const events = require('../events.js');
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
            getSettings: () => this.getSettings(),
            saveSettings: newSettings => this.saveSettings(newSettings),
        });
    }

    saveSettings(browsingSettings) {
        localStorage.setItem('settings', JSON.stringify(browsingSettings));
        events.notify(events.Success, 'Settings saved');
    }

    getSettings(settings) {
        const defaultSettings = {
            endlessScroll: false,
        };
        let ret = {};
        let userSettings = localStorage.getItem('settings');
        if (userSettings) {
            userSettings = JSON.parse(userSettings);
        }
        if (!userSettings) {
            userSettings = {};
        }
        for (let key of Object.keys(defaultSettings)) {
            if (key in userSettings) {
                ret[key] = userSettings[key];
            } else {
                ret[key] = defaultSettings[key];
            }
        }
        return ret;
    }
};

module.exports = new SettingsController();
