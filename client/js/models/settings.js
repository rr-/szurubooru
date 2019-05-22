'use strict';

const events = require('../events.js');

const defaultSettings = {
    listPosts: {
        safe: true,
        sketchy: true,
        unsafe: false,
    },
    upscaleSmallPosts: false,
    endlessScroll: false,
    keyboardShortcuts: true,
    transparencyGrid: true,
    fitMode: 'fit-both',
    tagSuggestions: true,
    autoplayVideos: false,
    postsPerPage: 42,
    tagUnderscoresAsSpaces: false,
};

class Settings extends events.EventTarget {
    save(newSettings, silent) {
        newSettings = Object.assign(this.get(), newSettings);
        localStorage.setItem('settings', JSON.stringify(newSettings));
        if (silent !== true) {
            this.dispatchEvent(new CustomEvent('change', {
                detail: {
                    settings: this.get(),
                },
            }));
        }
    }

    get() {
        let ret = Object.assign({}, defaultSettings);
        try {
            Object.assign(ret, JSON.parse(localStorage.getItem('settings')));
        } catch (e) {
        }
        return ret;
    }
};

module.exports = new Settings();
