'use strict';

const mousetrap = require('mousetrap');
const settings = require('../settings.js');

function bind(hotkey, func) {
    if (settings.getSettings().keyboardShortcuts) {
        mousetrap.bind(hotkey, func);
        return true;
    }
    return false;
}

function unbind(hotkey) {
    mousetrap.unbind(hotkey);
}

module.exports = {
    bind: bind,
    unbind: unbind,
};
