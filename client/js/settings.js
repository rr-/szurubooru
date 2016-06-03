'use strict';

const events = require('./events.js');

function saveSettings(browsingSettings, silent) {
    localStorage.setItem('settings', JSON.stringify(browsingSettings));
    if (silent !== true) {
        events.notify(events.Success, 'Settings saved');
        events.notify(events.SettingsChange);
    }
}

function getSettings(settings) {
    const defaultSettings = {
        listPosts: {
            safe: true,
            sketchy: true,
            unsafe: false,
        },
        upscaleSmallPosts: false,
        endlessScroll: false,
        keyboardShortcuts: true,
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

module.exports = {
    getSettings: getSettings,
    saveSettings: saveSettings,
};
