"use strict";

const events = require("../events.js");
const api = require("../api.js");
const misc = require("../util/misc.js");
const views = require("../util/views.js");
const TagInputControl = require("../controls/tag_input_control.js");

const template = views.getTemplate("tag-edit");

class TagEditView extends events.EventTarget {
    constructor(ctx) {
        super();

        this._tag = ctx.tag;
        this._hostNode = ctx.hostNode;
        views.replaceContent(this._hostNode, template(ctx));

        views.decorateValidator(this._formNode);

        if (this._namesFieldNode) {
            this._namesFieldNode.addEventListener("input", (e) =>
                this._evtNameInput(e)
            );
        }

        if (this._implicationsFieldNode) {
            new TagInputControl(
                this._implicationsFieldNode,
                this._tag.implications
            );
        }
        if (this._suggestionsFieldNode) {
            new TagInputControl(
                this._suggestionsFieldNode,
                this._tag.suggestions
            );
        }

        for (let node of this._formNode.querySelectorAll(
            "input, select, textarea"
        )) {
            node.addEventListener("change", (e) => {
                this.dispatchEvent(new CustomEvent("change"));
            });
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

    _evtNameInput(e) {
        const regex = new RegExp(api.getTagNameRegex());
        const list = misc.splitByWhitespace(this._namesFieldNode.value);

        if (!list.length) {
            this._namesFieldNode.setCustomValidity(
                "Tags must have at least one name."
            );
            return;
        }

        for (let item of list) {
            if (!regex.test(item)) {
                this._namesFieldNode.setCustomValidity(
                    `Tag name "${item}" contains invalid symbols.`
                );
                return;
            }
        }

        this._namesFieldNode.setCustomValidity("");
    }

    _evtSubmit(e) {
        e.preventDefault();
        this.dispatchEvent(
            new CustomEvent("submit", {
                detail: {
                    tag: this._tag,

                    names: this._namesFieldNode
                        ? misc.splitByWhitespace(this._namesFieldNode.value)
                        : undefined,

                    category: this._categoryFieldNode
                        ? this._categoryFieldNode.value
                        : undefined,

                    implications: this._implicationsFieldNode
                        ? misc.splitByWhitespace(
                              this._implicationsFieldNode.value
                          )
                        : undefined,

                    suggestions: this._suggestionsFieldNode
                        ? misc.splitByWhitespace(
                              this._suggestionsFieldNode.value
                          )
                        : undefined,

                    description: this._descriptionFieldNode
                        ? this._descriptionFieldNode.value
                        : undefined,
                },
            })
        );
    }

    get _formNode() {
        return this._hostNode.querySelector("form");
    }

    get _namesFieldNode() {
        return this._formNode.querySelector(".names input");
    }

    get _categoryFieldNode() {
        return this._formNode.querySelector(".category select");
    }

    get _implicationsFieldNode() {
        return this._formNode.querySelector(".implications input");
    }

    get _suggestionsFieldNode() {
        return this._formNode.querySelector(".suggestions input");
    }

    get _descriptionFieldNode() {
        return this._formNode.querySelector(".description textarea");
    }
}

module.exports = TagEditView;
