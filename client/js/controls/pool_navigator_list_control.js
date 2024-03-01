"use strict";

const events = require("../events.js");
const views = require("../util/views.js");
const PoolNavigatorControl = require("../controls/pool_navigator_control.js");

const template = views.getTemplate("pool-navigator-list");

class PoolNavigatorListControl extends events.EventTarget {
    constructor(hostNode, poolPostNearby) {
        super();
        this._hostNode = hostNode;
        this._poolPostNearby = poolPostNearby;
        this._indexToNode = {};

        for (let [i, entry] of this._poolPostNearby.entries()) {
            this._installPoolNavigatorNode(entry, i);
        }
    }

    get _poolNavigatorListNode() {
        return this._hostNode;
    }

    _installPoolNavigatorNode(poolPostNearby, i) {
        const poolListItemNode = document.createElement("div");
        const poolControl = new PoolNavigatorControl(
            poolListItemNode,
            poolPostNearby,
        );
        this._indexToNode[poolPostNearby.id] = poolListItemNode;
        this._poolNavigatorListNode.appendChild(poolListItemNode);
    }

    _uninstallPoolNavigatorNode(index) {
        const poolListItemNode = this._indexToNode[index];
        poolListItemNode.parentNode.removeChild(poolListItemNode);
    }

    _evtAdd(e) {
        this._installPoolNavigatorNode(e.detail.index);
    }

    _evtRemove(e) {
        this._uninstallPoolNavigatorNode(e.detail.index);
    }
}

module.exports = PoolNavigatorListControl;
