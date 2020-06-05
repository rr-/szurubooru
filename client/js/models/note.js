"use strict";

const events = require("../events.js");
const Point = require("./point.js");
const PointList = require("./point_list.js");

class Note extends events.EventTarget {
    constructor() {
        super();
        this._text = "…";
        this._polygon = new PointList();
    }

    get text() {
        return this._text;
    }

    get polygon() {
        return this._polygon;
    }

    set text(value) {
        this._text = value;
    }

    static fromResponse(response) {
        const note = new Note();
        note._updateFromResponse(response);
        return note;
    }

    _updateFromResponse(response) {
        this._text = response.text;
        this._polygon.clear();
        for (let point of response.polygon) {
            this._polygon.add(new Point(point[0], point[1]));
        }
    }
}

module.exports = Note;
