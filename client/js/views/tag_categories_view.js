'use strict';

const misc = require('../util/misc.js');
const views = require('../util/views.js');

class TagListHeaderView {
    constructor() {
        this._template = views.getTemplate('tag-categories');
    }

    render(ctx) {
        const target = document.getElementById('content-holder');
        const source = this._template(ctx);

        const form = source.querySelector('form');
        const newRowTemplate = source.querySelector('.add-template');
        const tableBody = source.querySelector('tbody');
        const addLink = source.querySelector('a.add');
        const saveButton = source.querySelector('button.save');

        newRowTemplate.parentNode.removeChild(newRowTemplate);
        views.decorateValidator(form);

        for (let row of tableBody.querySelectorAll('tr')) {
            this._addRowHandlers(row);
        }

        if (addLink) {
            addLink.addEventListener('click', e => {
                e.preventDefault();
                let newRow = newRowTemplate.cloneNode(true);
                tableBody.appendChild(newRow);
                this._addRowHandlers(row);
            });
        }

        form.addEventListener('submit', e => {
            this._evtSaveButtonClick(e, ctx, target);
        });

        views.listenToMessages(source);
        views.showView(target, source);
    }

    _evtSaveButtonClick(e, ctx, target) {
        e.preventDefault();

        views.clearMessages(target);
        const tableBody = target.querySelector('tbody');

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
            for (let row of tableBody.querySelectorAll('tr')) {
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

module.exports = TagListHeaderView;
