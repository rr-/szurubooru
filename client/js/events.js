'use strict';

class EventTarget {
    constructor() {
        this.eventTarget = document.createDocumentFragment();
        for (let method of [
            'addEventListener',
            'dispatchEvent',
            'removeEventListener'
        ]) {
            this[method] = this.eventTarget[method].bind(this.eventTarget);
        }
    }
};

module.exports = {
    Success: 'success',
    Error: 'error',
    Info: 'info',

    EventTarget: EventTarget,
};
