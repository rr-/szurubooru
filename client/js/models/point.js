"use strict";

const events = require("../events.js");

class Point extends events.EventTarget {
    constructor(x, y) {
        super();
        this._x = x;
        this._y = y;
    }

    get x() {
        return this._x;
    }

    get y() {
        return this._y;
    }

    set x(value) {
        this._x = value;
        this.dispatchEvent(
            new CustomEvent("change", { detail: { point: this } })
        );
    }

    set y(value) {
        this._y = value;
        this.dispatchEvent(
            new CustomEvent("change", { detail: { point: this } })
        );
    }
}

module.exports = Point;
