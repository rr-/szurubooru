"use strict";

const api = require("../api.js");
const pools = require("../pools.js");
const misc = require("../util/misc.js");
const uri = require("../util/uri.js");
const Pool = require("../models/pool.js");
const settings = require("../models/settings.js");
const events = require("../events.js");
const views = require("../util/views.js");
const PoolAutoCompleteControl = require("./pool_auto_complete_control.js");

const SOURCE_INIT = "init";
const SOURCE_IMPLICATION = "implication";
const SOURCE_USER_INPUT = "user-input";
const SOURCE_CLIPBOARD = "clipboard";

const template = views.getTemplate("pool-input");

function _fadeOutListItemNodeStatus(listItemNode) {
    if (listItemNode.classList.length) {
        if (listItemNode.fadeTimeout) {
            window.clearTimeout(listItemNode.fadeTimeout);
        }
        listItemNode.fadeTimeout = window.setTimeout(() => {
            while (listItemNode.classList.length) {
                listItemNode.classList.remove(listItemNode.classList.item(0));
            }
            listItemNode.fadeTimeout = null;
        }, 2500);
    }
}

class PoolInputControl extends events.EventTarget {
    constructor(hostNode, poolList) {
        super();
        this.pools = poolList;
        this._hostNode = hostNode;
        this._poolToListItemNode = new Map();

        // dom
        const editAreaNode = template();
        this._editAreaNode = editAreaNode;
        this._poolInputNode = editAreaNode.querySelector("input");
        this._poolListNode = editAreaNode.querySelector("ul.compact-pools");

        this._autoCompleteControl = new PoolAutoCompleteControl(
            this._poolInputNode,
            {
                getTextToFind: () => {
                    return this._poolInputNode.value;
                },
                confirm: (pool) => {
                    this._poolInputNode.value = "";
                    this.addPool(pool, SOURCE_USER_INPUT);
                },
                delete: (pool) => {
                    this._poolInputNode.value = "";
                    this.deletePool(pool);
                },
                verticalShift: -2,
            }
        );

        // show
        this._hostNode.style.display = "none";
        this._hostNode.parentNode.insertBefore(
            this._editAreaNode,
            hostNode.nextSibling
        );

        // add existing pools
        for (let pool of [...this.pools]) {
            const listItemNode = this._createListItemNode(pool);
            this._poolListNode.appendChild(listItemNode);
        }
    }

    addPool(pool, source) {
        if (source !== SOURCE_INIT && this.pools.hasPoolId(pool.id)) {
            return Promise.resolve();
        }

        this.pools.add(pool, false);

        const listItemNode = this._createListItemNode(pool);
        if (!pool.category) {
            listItemNode.classList.add("new");
        }
        this._poolListNode.prependChild(listItemNode);
        _fadeOutListItemNodeStatus(listItemNode);

        this.dispatchEvent(
            new CustomEvent("add", {
                detail: { pool: pool, source: source },
            })
        );
        this.dispatchEvent(new CustomEvent("change"));

        return Promise.resolve();
    }

    deletePool(pool) {
        if (!this.pools.hasPoolId(pool.id)) {
            return;
        }
        this.pools.removeById(pool.id);
        this._hideAutoComplete();

        this._deleteListItemNode(pool);

        this.dispatchEvent(
            new CustomEvent("remove", {
                detail: { pool: pool },
            })
        );
        this.dispatchEvent(new CustomEvent("change"));
    }

    _createListItemNode(pool) {
        const className = pool.category
            ? misc.makeCssName(pool.category, "pool")
            : null;

        const poolLinkNode = document.createElement("a");
        if (className) {
            poolLinkNode.classList.add(className);
        }
        poolLinkNode.setAttribute(
            "href",
            uri.formatClientLink("pool", pool.names[0])
        );

        const poolIconNode = document.createElement("i");
        poolIconNode.classList.add("fa");
        poolIconNode.classList.add("fa-pool");
        poolLinkNode.appendChild(poolIconNode);

        const searchLinkNode = document.createElement("a");
        if (className) {
            searchLinkNode.classList.add(className);
        }
        searchLinkNode.setAttribute(
            "href",
            uri.formatClientLink("posts", { query: "pool:" + pool.id })
        );
        searchLinkNode.textContent = pool.names[0] + " ";

        const usagesNode = document.createElement("span");
        usagesNode.classList.add("pool-usages");
        usagesNode.setAttribute("data-pseudo-content", pool.postCount);

        const removalLinkNode = document.createElement("a");
        removalLinkNode.classList.add("remove-pool");
        removalLinkNode.setAttribute("href", "");
        removalLinkNode.setAttribute("data-pseudo-content", "×");
        removalLinkNode.addEventListener("click", (e) => {
            e.preventDefault();
            this.deletePool(pool);
        });

        const listItemNode = document.createElement("li");
        listItemNode.appendChild(removalLinkNode);
        listItemNode.appendChild(poolLinkNode);
        listItemNode.appendChild(searchLinkNode);
        listItemNode.appendChild(usagesNode);
        for (let name of pool.names) {
            this._poolToListItemNode.set(name, listItemNode);
        }
        return listItemNode;
    }

    _deleteListItemNode(pool) {
        const listItemNode = this._getListItemNode(pool);
        if (listItemNode) {
            listItemNode.parentNode.removeChild(listItemNode);
        }
        for (let name of pool.names) {
            this._poolToListItemNode.delete(name);
        }
    }

    _getListItemNode(pool) {
        return this._poolToListItemNode.get(pool.names[0]);
    }

    _hideAutoComplete() {
        this._autoCompleteControl.hide();
    }
}

module.exports = PoolInputControl;
