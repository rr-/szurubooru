'use strict';

let pendingMessages = new Map();
let listeners = new Map();

function unlisten(messageClass) {
    listeners.set(messageClass, []);
}

function listen(messageClass, handler) {
    if (pendingMessages.has(messageClass)) {
        let newPendingMessages = [];
        for (let message of pendingMessages.get(messageClass)) {
            if (!handler(message)) {
                newPendingMessages.push(message);
            }
        }
        pendingMessages.set(messageClass, newPendingMessages);
    }
    if (!listeners.has(messageClass)) {
        listeners.set(messageClass, []);
    }
    listeners.get(messageClass).push(handler);
}

function notify(messageClass, message) {
    if (!listeners.has(messageClass) || !listeners.get(messageClass).length) {
        if (!pendingMessages.has(messageClass)) {
            pendingMessages.set(messageClass, []);
        }
        pendingMessages.get(messageClass).push(message);
        return;
    }
    for (let handler of listeners.get(messageClass)) {
        handler(message);
    }
}

module.exports = {
    Success: 'success',
    Error: 'error',
    Info: 'info',
    Authentication: 'auth',
    SettingsChange: 'settings-change',
    TagsChange: 'tags-change',

    notify: notify,
    listen: listen,
    unlisten: unlisten,
};
