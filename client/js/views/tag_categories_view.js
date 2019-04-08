'use strict';

const events = require('../events.js');
const views = require('../util/views.js');
const TagCategory = require('../models/tag_category.js');

const template = views.getTemplate('tag-categories');
const rowTemplate = views.getTemplate('tag-category-row');

class TagCategoriesView extends events.EventTarget {
    constructor(ctx) {
        super();
        this._ctx = ctx;
        this._hostNode = document.getElementById('content-holder');

        views.replaceContent(this._hostNode, template(ctx));
        views.syncScrollPosition();
        views.decorateValidator(this._formNode);

        const categoriesToAdd = Array.from(ctx.tagCategories);
        categoriesToAdd.sort((a, b) => {
            if (b.isDefault) {
                return 1;
            } else if (a.isDefault) {
                return -1;
            }
            return a.name.localeCompare(b.name);
        });
        for (let tagCategory of categoriesToAdd) {
            this._addTagCategoryRowNode(tagCategory);
        }

        if (this._addLinkNode) {
            this._addLinkNode.addEventListener(
                'click', e => this._evtAddButtonClick(e));
        }

        ctx.tagCategories.addEventListener(
            'add', e => this._evtTagCategoryAdded(e));

        ctx.tagCategories.addEventListener(
            'remove', e => this._evtTagCategoryDeleted(e));

        this._formNode.addEventListener(
            'submit', e => this._evtSaveButtonClick(e, ctx));
    }

    enableForm() {
        views.enableForm(this._formNode);
    }

    disableForm() {
        views.disableForm(this._formNode);
    }

    clearMessages() {
        views.clearMessages(this._hostNode);
    }

    showSuccess(message) {
        views.showSuccess(this._hostNode, message);
    }

    showError(message) {
        views.showError(this._hostNode, message);
    }

    get _formNode() {
        return this._hostNode.querySelector('form');
    }

    get _tableBodyNode() {
        return this._hostNode.querySelector('tbody');
    }

    get _addLinkNode() {
        return this._hostNode.querySelector('a.add');
    }

    _addTagCategoryRowNode(tagCategory) {
        const rowNode = rowTemplate(
            Object.assign(
                {}, this._ctx, {tagCategory: tagCategory}));

        const nameInput = rowNode.querySelector('.name input');
        if (nameInput) {
            nameInput.addEventListener(
                'change', e => this._evtNameChange(e, rowNode));
        }

        const colorInput = rowNode.querySelector('.color input');
        if (colorInput) {
            colorInput.addEventListener(
                'change', e => this._evtColorChange(e, rowNode));
        }

        const removeLinkNode = rowNode.querySelector('.remove a');
        if (removeLinkNode) {
            removeLinkNode.addEventListener(
                'click', e => this._evtDeleteButtonClick(e, rowNode));
        }

        const defaultLinkNode = rowNode.querySelector('.set-default a');
        if (defaultLinkNode) {
            defaultLinkNode.addEventListener(
                'click', e => this._evtSetDefaultButtonClick(e, rowNode));
        }

        this._tableBodyNode.appendChild(rowNode);

        rowNode._tagCategory = tagCategory;
        tagCategory._rowNode = rowNode;
    }

    _removeTagCategoryRowNode(tagCategory) {
        const rowNode = tagCategory._rowNode;
        rowNode.parentNode.removeChild(rowNode);
    }

    _evtTagCategoryAdded(e) {
        this._addTagCategoryRowNode(e.detail.tagCategory);
    }

    _evtTagCategoryDeleted(e) {
        this._removeTagCategoryRowNode(e.detail.tagCategory);
    }

    _evtAddButtonClick(e) {
        e.preventDefault();
        this._ctx.tagCategories.add(new TagCategory());
    }

    _evtNameChange(e, rowNode) {
        rowNode._tagCategory.name = e.target.value;
    }

    _evtColorChange(e, rowNode) {
        e.target.value = e.target.value.toLowerCase();
        rowNode._tagCategory.color = e.target.value;
    }

    _evtDeleteButtonClick(e, rowNode, link) {
        e.preventDefault();
        if (e.target.classList.contains('inactive')) {
            return;
        }
        this._ctx.tagCategories.remove(rowNode._tagCategory);
    }

    _evtSetDefaultButtonClick(e, rowNode) {
        e.preventDefault();
        this._ctx.tagCategories.defaultCategory = rowNode._tagCategory;
        const oldRowNode = rowNode.parentNode.querySelector('tr.default');
        if (oldRowNode) {
            oldRowNode.classList.remove('default');
        }
        rowNode.classList.add('default');
    }

    _evtSaveButtonClick(e, ctx) {
        e.preventDefault();
        this.dispatchEvent(new CustomEvent('submit'));
    }
}

module.exports = TagCategoriesView;
