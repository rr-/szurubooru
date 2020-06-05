"use strict";

const events = require("../events.js");
const misc = require("../util/misc.js");
const search = require("../util/search.js");
const views = require("../util/views.js");
const PoolAutoCompleteControl = require("../controls/pool_auto_complete_control.js");

const template = views.getTemplate("pools-header");

class PoolsHeaderView extends events.EventTarget {
    constructor(ctx) {
        super();

        this._hostNode = ctx.hostNode;
        views.replaceContent(this._hostNode, template(ctx));

        if (this._queryInputNode) {
            this._autoCompleteControl = new PoolAutoCompleteControl(
                this._queryInputNode,
                {
                    confirm: (pool) =>
                        this._autoCompleteControl.replaceSelectedText(
                            misc.escapeSearchTerm(pool.names[0]),
                            true
                        ),
                }
            );
        }

        search.searchInputNodeFocusHelper(this._queryInputNode);

        this._formNode.addEventListener("submit", (e) => this._evtSubmit(e));
    }

    get _formNode() {
        return this._hostNode.querySelector("form");
    }

    get _queryInputNode() {
        return this._hostNode.querySelector("[name=search-text]");
    }

    _evtSubmit(e) {
        e.preventDefault();
        this._queryInputNode.blur();
        this.dispatchEvent(
            new CustomEvent("navigate", {
                detail: {
                    parameters: {
                        query: this._queryInputNode.value,
                        page: 1,
                    },
                },
            })
        );
    }
}

module.exports = PoolsHeaderView;
