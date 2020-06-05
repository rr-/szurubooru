"use strict";

class EventTarget {
    constructor() {
        this.eventTarget = document.createDocumentFragment();
        for (let method of [
            "addEventListener",
            "dispatchEvent",
            "removeEventListener",
        ]) {
            this[method] = this.eventTarget[method].bind(this.eventTarget);
        }
    }
}

function proxyEvent(source, target, sourceEventType, targetEventType) {
    if (!source.addEventListener) {
        return;
    }
    if (!targetEventType) {
        targetEventType = sourceEventType;
    }
    source.addEventListener(sourceEventType, (e) => {
        target.dispatchEvent(
            new CustomEvent(targetEventType, {
                detail: e.detail,
            })
        );
    });
}

module.exports = {
    Success: "success",
    Error: "error",
    Info: "info",

    proxyEvent: proxyEvent,
    EventTarget: EventTarget,
};
