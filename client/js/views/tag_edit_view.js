'use strict';

const config = require('../config.js');
const events = require('../events.js');
const views = require('../util/views.js');
const TagInputControl = require('../controls/tag_input_control.js');

const template = views.getTemplate('tag-edit');

function _split(str) {
    return str.split(/\s+/).filter(s => s);
}

class TagEditView extends events.EventTarget {
    constructor(ctx) {
        super();

        this._tag = ctx.tag;
        this._hostNode = ctx.hostNode;
        views.replaceContent(this._hostNode, template(ctx));

        views.decorateValidator(this._formNode);

        if (this._namesFieldNode) {
            this._namesFieldNode.addEventListener(
                'input', e => this._evtNameInput(e));
        }

        if (this._implicationsFieldNode) {
            new TagInputControl(this._implicationsFieldNode);
        }
        if (this._suggestionsFieldNode) {
            new TagInputControl(this._suggestionsFieldNode);
        }

        this._formNode.addEventListener('submit', e => this._evtSubmit(e));
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
        const regex = new RegExp(config.tagNameRegex);
        const list = this._namesFieldNode.value.split(/\s+/).filter(t => t);

        if (!list.length) {
            this._namesFieldNode.setCustomValidity(
                'Tags must have at least one name.');
            return;
        }

        for (let item of list) {
            if (!regex.test(item)) {
                this._namesFieldNode.setCustomValidity(
                    `Tag name "${item}" contains invalid symbols.`);
                return;
            }
        }

        this._namesFieldNode.setCustomValidity('');
    }

    _evtSubmit(e) {
        e.preventDefault();
        this.dispatchEvent(new CustomEvent('submit', {
            detail: {
                tag: this._tag,
                names: _split(this._namesFieldNode.value),
                category: this._categoryFieldNode.value,
                implications: _split(this._implicationsFieldNode.value),
                suggestions: _split(this._suggestionsFieldNode.value),
                description: this._descriptionFieldNode.value,
            },
        }));
    }

    get _formNode() {
        return this._hostNode.querySelector('form');
    }

    get _namesFieldNode() {
        return this._formNode.querySelector('.names input');
    }

    get _categoryFieldNode() {
        return this._formNode.querySelector('.category select');
    }

    get _implicationsFieldNode() {
        return this._formNode.querySelector('.implications input');
    }

    get _suggestionsFieldNode() {
        return this._formNode.querySelector('.suggestions input');
    }

    get _descriptionFieldNode() {
        return this._formNode.querySelector('.description textarea');
    }
}

module.exports = TagEditView;
