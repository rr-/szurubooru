"use strict";

const api = require("../api.js");
const misc = require("../util/misc.js");
const events = require("../events.js");
const views = require("../util/views.js");

const template = views.getTemplate("pool-navigator");

class PoolNavigatorControl extends events.EventTarget {
    constructor(hostNode, poolPostNearby) {
        super();
        this._hostNode = hostNode;
        this._poolPostNearby = poolPostNearby;

        views.replaceContent(
            this._hostNode,
            template({
                pool: poolPostNearby,
                parameters: { query: `pool:${poolPostNearby.id}` },
                linkClass: misc.makeCssName(poolPostNearby.category, "pool"),
                canViewPosts: api.hasPrivilege("posts:view"),
                canViewPools: api.hasPrivilege("pools:view"),
                firstPost: poolPostNearby.firstPost,
                previousPost: poolPostNearby.previousPost,
                nextPost: poolPostNearby.nextPost,
                lastPost: poolPostNearby.lastPost,
                getPrettyName: misc.getPrettyName,
            })
        );
    }
}

module.exports = PoolNavigatorControl;