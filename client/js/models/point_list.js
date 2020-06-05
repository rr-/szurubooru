"use strict";

const AbstractList = require("./abstract_list.js");
const Point = require("./point.js");

class PointList extends AbstractList {
    get firstPoint() {
        return this._list[0];
    }

    get secondLastPoint() {
        return this._list[this._list.length - 2];
    }

    get lastPoint() {
        return this._list[this._list.length - 1];
    }
}

PointList._itemClass = Point;
PointList._itemName = "point";

module.exports = PointList;
