"use strict";

const events = require("../events.js");
const views = require("../util/views.js");
const PoolCategory = require("../models/pool_category.js");

const template = views.getTemplate("pool-categories");
const rowTemplate = views.getTemplate("pool-category-row");

class PoolCategoriesView extends events.EventTarget {
    constructor(ctx) {
        super();
        this._ctx = ctx;
        this._hostNode = document.getElementById("content-holder");

        views.replaceContent(this._hostNode, template(ctx));
        views.syncScrollPosition();
        views.decorateValidator(this._formNode);

        const categoriesToAdd = Array.from(ctx.poolCategories);
        categoriesToAdd.sort((a, b) => {
            if (b.isDefault) {
                return 1;
            } else if (a.isDefault) {
                return -1;
            }
            return a.name.localeCompare(b.name);
        });
        for (let poolCategory of categoriesToAdd) {
            this._addPoolCategoryRowNode(poolCategory);
        }

        if (this._addLinkNode) {
            this._addLinkNode.addEventListener("click", (e) =>
                this._evtAddButtonClick(e)
            );
        }

        ctx.poolCategories.addEventListener("add", (e) =>
            this._evtPoolCategoryAdded(e)
        );

        ctx.poolCategories.addEventListener("remove", (e) =>
            this._evtPoolCategoryDeleted(e)
        );

        this._formNode.addEventListener("submit", (e) =>
            this._evtSaveButtonClick(e, ctx)
        );
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
        return this._hostNode.querySelector("form");
    }

    get _tableBodyNode() {
        return this._hostNode.querySelector("tbody");
    }

    get _addLinkNode() {
        return this._hostNode.querySelector("a.add");
    }

    _addPoolCategoryRowNode(poolCategory) {
        const rowNode = rowTemplate(
            Object.assign({}, this._ctx, { poolCategory: poolCategory })
        );

        const nameInput = rowNode.querySelector(".name input");
        if (nameInput) {
            nameInput.addEventListener("change", (e) =>
                this._evtNameChange(e, rowNode)
            );
        }

        const colorInput = rowNode.querySelector(".color input");
        if (colorInput) {
            colorInput.addEventListener("change", (e) =>
                this._evtColorChange(e, rowNode)
            );
        }

        const removeLinkNode = rowNode.querySelector(".remove a");
        if (removeLinkNode) {
            removeLinkNode.addEventListener("click", (e) =>
                this._evtDeleteButtonClick(e, rowNode)
            );
        }

        const defaultLinkNode = rowNode.querySelector(".set-default a");
        if (defaultLinkNode) {
            defaultLinkNode.addEventListener("click", (e) =>
                this._evtSetDefaultButtonClick(e, rowNode)
            );
        }

        this._tableBodyNode.appendChild(rowNode);

        rowNode._poolCategory = poolCategory;
        poolCategory._rowNode = rowNode;
    }

    _removePoolCategoryRowNode(poolCategory) {
        const rowNode = poolCategory._rowNode;
        rowNode.parentNode.removeChild(rowNode);
    }

    _evtPoolCategoryAdded(e) {
        this._addPoolCategoryRowNode(e.detail.poolCategory);
    }

    _evtPoolCategoryDeleted(e) {
        this._removePoolCategoryRowNode(e.detail.poolCategory);
    }

    _evtAddButtonClick(e) {
        e.preventDefault();
        this._ctx.poolCategories.add(new PoolCategory());
    }

    _evtNameChange(e, rowNode) {
        rowNode._poolCategory.name = e.target.value;
    }

    _evtColorChange(e, rowNode) {
        e.target.value = e.target.value.toLowerCase();
        rowNode._poolCategory.color = e.target.value;
    }

    _evtDeleteButtonClick(e, rowNode, link) {
        e.preventDefault();
        if (e.target.classList.contains("inactive")) {
            return;
        }
        this._ctx.poolCategories.remove(rowNode._poolCategory);
    }

    _evtSetDefaultButtonClick(e, rowNode) {
        e.preventDefault();
        this._ctx.poolCategories.defaultCategory = rowNode._poolCategory;
        const oldRowNode = rowNode.parentNode.querySelector("tr.default");
        if (oldRowNode) {
            oldRowNode.classList.remove("default");
        }
        rowNode.classList.add("default");
    }

    _evtSaveButtonClick(e, ctx) {
        e.preventDefault();
        this.dispatchEvent(new CustomEvent("submit"));
    }
}

module.exports = PoolCategoriesView;
