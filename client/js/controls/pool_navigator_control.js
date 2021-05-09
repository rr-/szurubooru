"use strict";

const api = require("../api.js");
const misc = require("../util/misc.js");
const events = require("../events.js");
const views = require("../util/views.js");

const template = views.getTemplate("pool-navigator");

class PoolNavigatorControl extends events.EventTarget {
    constructor(hostNode, poolPostAround, isActivePool) {
        super();
        this._hostNode = hostNode;
        this._poolPostAround = poolPostAround;
        this._isActivePool = isActivePool;

        views.replaceContent(
            this._hostNode,
            template({
                pool: poolPostAround.pool,
                parameters: { query: `pool:${poolPostAround.pool.id}` },
                linkClass: misc.makeCssName(poolPostAround.pool.category, "pool"),
                canViewPosts: api.hasPrivilege("posts:view"),
                canViewPools: api.hasPrivilege("pools:view"),
                prevPost: poolPostAround.prevPost,
                nextPost: poolPostAround.nextPost,
                isActivePool: isActivePool
            })
        );
    }
}

module.exports = PoolNavigatorControl;
