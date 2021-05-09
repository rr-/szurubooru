"use strict";

const api = require("../api.js");
const misc = require("../util/misc.js");
const events = require("../events.js");
const views = require("../util/views.js");

const template = views.getTemplate("pool-navigator");

class PoolNavigatorControl extends events.EventTarget {
    constructor(hostNode, pool) {
        super();
        this._hostNode = hostNode;
        this._pool = pool;
    }

    // get _formNode() {
    //     return this._hostNode.querySelector("form");
    // }

    // get _scoreContainerNode() {
    //     return this._hostNode.querySelector(".score-container");
    // }
}

module.exports = PoolNavigatorControl;
