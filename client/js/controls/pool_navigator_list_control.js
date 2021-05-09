"use strict";

const events = require("../events.js");
const views = require("../util/views.js");
const PoolNavigatorControl = require("../controls/pool_navigator_control.js");

const template = views.getTemplate("pool-navigator-list");

class PoolNavigatorListControl extends events.EventTarget {
    constructor(hostNode, a) {
        super();
        this._hostNode = hostNode;

        const poolList = [];
        for (let pool of poolList) {
            this._installPoolNavigatorNode(pool);
        }
    }

    get _poolNavigatorListNode() {
        return this._hostNode.querySelector("ul");
    }

    _installPoolNavigatorNode(pool) {
        const poolListItemNode = document.createElement("li");
        const poolControl = new PoolNavigatorControl(
            pool
        );
        // events.proxyEvent(commentControl, this, "submit");
        // events.proxyEvent(commentControl, this, "score");
        // events.proxyEvent(commentControl, this, "delete");
        // this._commentIdToNode[comment.id] = commentListItemNode;
        this._poolNavigatorListNode.appendChild(poolListItemNode);
    }

    _uninstallCommentNode(pool) {
        const poolListItemNode = this._commentIdToNode[pool.id];
        poolListItemNode.parentNode.removeChild(poolListItemNode);
    }

    // _evtAdd(e) {
    //     this._installPoolNode(e.detail.comment);
    // }

    // _evtRemove(e) {
    //     this._uninstallPoolNode(e.detail.comment);
    // }
}

module.exports = PoolNavigatorListControl;
