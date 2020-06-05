"use strict";

const mousetrap = require("mousetrap");
const settings = require("../models/settings.js");

let paused = false;
const _originalStopCallback = mousetrap.prototype.stopCallback;
// eslint-disable-next-line func-names
mousetrap.prototype.stopCallback = function (...args) {
    var self = this;
    if (paused) {
        return true;
    }
    return _originalStopCallback.call(self, ...args);
};

function bind(hotkey, func) {
    if (settings.get().keyboardShortcuts) {
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
    pause: () => {
        paused = true;
    },
    unpause: () => {
        paused = false;
    },
};
