"use strict";

const events = require("../events.js");
const api = require("../api.js");
const views = require("../util/views.js");
const TagAutoCompleteControl = require("../controls/tag_auto_complete_control.js");

const template = views.getTemplate("tag-merge");

class TagMergeView extends events.EventTarget {
    constructor(ctx) {
        super();

        this._tag = ctx.tag;
        this._hostNode = ctx.hostNode;
        ctx.tagNamePattern = api.getTagNameRegex();
        views.replaceContent(this._hostNode, template(ctx));

        views.decorateValidator(this._formNode);
        if (this._targetTagFieldNode) {
            this._autoCompleteControl = new TagAutoCompleteControl(
                this._targetTagFieldNode,
                {
                    confirm: (tag) =>
                        this._autoCompleteControl.replaceSelectedText(
                            tag.names[0],
                            false
                        ),
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
        this.dispatchEvent(
            new CustomEvent("submit", {
                detail: {
                    tag: this._tag,
                    targetTagName: this._targetTagFieldNode.value,
                    addAlias: this._addAliasCheckboxNode.checked,
                },
            })
        );
    }

    get _formNode() {
        return this._hostNode.querySelector("form");
    }

    get _targetTagFieldNode() {
        return this._formNode.querySelector("input[name=target-tag]");
    }

    get _addAliasCheckboxNode() {
        return this._formNode.querySelector("input[name=alias]");
    }
}

module.exports = TagMergeView;
