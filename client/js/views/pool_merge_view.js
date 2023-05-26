"use strict";

const events = require("../events.js");
const api = require("../api.js");
const views = require("../util/views.js");
const PoolAutoCompleteControl = require("../controls/pool_auto_complete_control.js");

const template = views.getTemplate("pool-merge");

class PoolMergeView extends events.EventTarget {
    constructor(ctx) {
        super();

        this._pool = ctx.pool;
        this._hostNode = ctx.hostNode;
        this._targetPoolId = null;
        ctx.poolNamePattern = api.getPoolNameRegex();
        views.replaceContent(this._hostNode, template(ctx));

        views.decorateValidator(this._formNode);
        if (this._targetPoolFieldNode) {
            this._autoCompleteControl = new PoolAutoCompleteControl(
                this._targetPoolFieldNode,
                {
                    confirm: (pool) => {
                        this._targetPoolId = pool.id;
                        this._autoCompleteControl.replaceSelectedText(
                            pool.names[0],
                            false
                        );
                    },
                }
            );
        }

        this._formNode.addEventListener("submit", (e) => this._evtSubmit(e));
    }

    clearMessages() {
        views.clearMessages(this._hostNode);
    }

    enableForm() {
        views.enableForm(this._formNode);
    }

    disableForm() {
        views.disableForm(this._formNode);
    }

    showSuccess(message) {
        views.showSuccess(this._hostNode, message);
    }

    showError(message) {
        views.showError(this._hostNode, message);
    }

    _evtSubmit(e) {
        e.preventDefault();
        if (!this._targetPoolId) {
            this.clearMessages();
            this.showError("You must select a pool name from autocomplete.");
            return;
        }
        this.dispatchEvent(
            new CustomEvent("submit", {
                detail: {
                    pool: this._pool,
                    targetPoolId: this._targetPoolId,
                },
            })
        );
    }

    get _formNode() {
        return this._hostNode.querySelector("form");
    }

    get _targetPoolFieldNode() {
        return this._formNode.querySelector("input[name=target-pool]");
    }

    get _addAliasCheckboxNode() {
        return this._formNode.querySelector("input[name=alias]");
    }
}

module.exports = PoolMergeView;
