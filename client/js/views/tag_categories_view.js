'use strict';

const views = require('../util/views.js');

const template = views.getTemplate('tag-categories');

class TagCategoriesView {
    constructor(ctx) {
        this._hostNode = document.getElementById('content-holder');
        const sourceNode = template(ctx);

        const formNode = sourceNode.querySelector('form');
        const newRowTemplate = sourceNode.querySelector('.add-template');
        const tableBodyNode = sourceNode.querySelector('tbody');
        const addLinkNode = sourceNode.querySelector('a.add');

        newRowTemplate.parentNode.removeChild(newRowTemplate);
        views.decorateValidator(formNode);

        for (let row of tableBodyNode.querySelectorAll('tr')) {
            this._addRowHandlers(row);
        }

        if (addLinkNode) {
            addLinkNode.addEventListener('click', e => {
                e.preventDefault();
                let newRow = newRowTemplate.cloneNode(true);
                tableBody.appendChild(newRow);
                this._addRowHandlers(row);
            });
        }

        formNode.addEventListener('submit', e => {
            this._evtSaveButtonClick(e, ctx);
        });

        views.replaceContent(this._hostNode, sourceNode);
    }

    showSuccess(message) {
        views.showSuccess(this._hostNode, message);
    }

    showError(message) {
        views.showError(this._hostNode, message);
    }

    _evtSaveButtonClick(e, ctx) {
        e.preventDefault();

        views.clearMessages(this._hostNode);
        const tableBodyNode = this._hostNode.querySelector('tbody');

        ctx.getCategories().then(categories => {
            let existingCategories = {};
            for (let category of categories) {
                existingCategories[category.name] = category;
            }

            let defaultCategory = null;
            let addedCategories = [];
            let removedCategories = [];
            let changedCategories = [];
            let allNames = [];
            for (let row of tableBodyNode.querySelectorAll('tr')) {
                let name = row.getAttribute('data-category');
                let category = {
                    originalName: name,
                    name: row.querySelector('.name input').value,
                    color: row.querySelector('.color input').value,
                };
                if (row.classList.contains('default')) {
                    defaultCategory = category.name;
                }
                if (!name) {
                    if (category.name) {
                        addedCategories.push(category);
                    }
                } else {
                    const existingCategory = existingCategories[name];
                    if (existingCategory.color !== category.color ||
                            existingCategory.name !== category.name) {
                        changedCategories.push(category);
                    }
                }
                allNames.push(name);
            }
            for (let name of Object.keys(existingCategories)) {
                if (allNames.indexOf(name) === -1) {
                    removedCategories.push(name);
                }
            }
            ctx.saveChanges(
                addedCategories,
                changedCategories,
                removedCategories,
                defaultCategory);
        });
    }

    _evtRemoveButtonClick(e, row, link) {
        e.preventDefault();
        if (link.classList.contains('inactive')) {
            return;
        }
        row.parentNode.removeChild(row);
    }

    _evtSetDefaultButtonClick(e, row) {
        e.preventDefault();
        const oldRowNode = row.parentNode.querySelector('tr.default');
        if (oldRowNode) {
            oldRowNode.classList.remove('default');
        }
        row.classList.add('default');
    }

    _addRowHandlers(row) {
        const removeLink = row.querySelector('.remove a');
        if (removeLink) {
            removeLink.addEventListener(
                'click', e => this._evtRemoveButtonClick(e, row, removeLink));
        }

        const defaultLink = row.querySelector('.set-default a');
        if (defaultLink) {
            defaultLink.addEventListener(
                'click', e => this._evtSetDefaultButtonClick(e, row));
        }
    }
}

module.exports = TagCategoriesView;
