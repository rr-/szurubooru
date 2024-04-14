"use strict";

const events = require("../events.js");
const views = require("../util/views.js");
const PoolNavigatorControl = require("../controls/pool_navigator_control.js");

const template = views.getTemplate("pool-navigator-list");

class PoolNavigatorListControl extends events.EventTarget {
    constructor(hostNode, poolPostsAround, activePool) {
        super();
        this._hostNode = hostNode;
        this._poolPostsAround = poolPostsAround;
        this._activePool = activePool;
        this._indexToNode = {};

        for (let [i, entry] of this._poolPostsAround.entries()) {
            this._installPoolNavigatorNode(entry, i);
        }
    }

    get _poolNavigatorListNode() {
        return this._hostNode;
    }

    _installPoolNavigatorNode(poolPostAround, i) {
        const isActivePool = poolPostAround.pool.id == this._activePool
        const poolListItemNode = document.createElement("div");
        const poolControl = new PoolNavigatorControl(
            poolListItemNode,
            poolPostAround,
            isActivePool
        );
        // events.proxyEvent(commentControl, this, "submit");
        // events.proxyEvent(commentControl, this, "score");
        // events.proxyEvent(commentControl, this, "delete");
        this._indexToNode[poolPostAround.id] = poolListItemNode;
        if (isActivePool) {
            this._poolNavigatorListNode.insertBefore(poolListItemNode, this._poolNavigatorListNode.firstChild);
        } else {
            this._poolNavigatorListNode.appendChild(poolListItemNode);
        }
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
