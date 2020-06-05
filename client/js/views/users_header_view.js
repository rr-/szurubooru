"use strict";

const events = require("../events.js");
const search = require("../util/search.js");
const views = require("../util/views.js");

const template = views.getTemplate("users-header");

class UsersHeaderView extends events.EventTarget {
    constructor(ctx) {
        super();

        this._hostNode = ctx.hostNode;
        views.replaceContent(this._hostNode, template(ctx));

        search.searchInputNodeFocusHelper(this._queryInputNode);

        this._formNode.addEventListener("submit", (e) => this._evtSubmit(e));
    }

    get _formNode() {
        return this._hostNode.querySelector("form");
    }

    get _queryInputNode() {
        return this._formNode.querySelector("[name=search-text]");
    }

    _evtSubmit(e) {
        e.preventDefault();
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

module.exports = UsersHeaderView;
