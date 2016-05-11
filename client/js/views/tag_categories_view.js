'use strict';

const misc = require('../util/misc.js');
const views = require('../util/views.js');

class TagListHeaderView {
    constructor() {
        this.template = views.getTemplate('tag-categories');
    }

    _saveButtonClickHandler(e, ctx, target) {
        e.preventDefault();

        views.clearMessages(target);
        const tableBody = target.querySelector('tbody');

        ctx.getCategories().then(categories => {
            let existingCategories = {};
            for (let category of categories) {
                existingCategories[category.name] = category;
            }

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
                addedCategories, changedCategories, removedCategories);
        });
    }

    _removeButtonClickHandler(e, row, link) {
        e.preventDefault();
        if (link.classList.contains('inactive')) {
            return;
        }
        row.parentNode.removeChild(row);
    }

    _addRemoveButtonClickHandler(row) {
        const link = row.querySelector('a.remove');
        if (!link) {
            return;
        }
        link.addEventListener(
            'click', e => this._removeButtonClickHandler(e, row, link));
    }

    render(ctx) {
        const target = document.getElementById('content-holder');
        const source = this.template(ctx);

        const form = source.querySelector('form');
        const newRowTemplate = source.querySelector('.add-template');
        const tableBody = source.querySelector('tbody');
        const addLink = source.querySelector('a.add');
        const saveButton = source.querySelector('button.save');

        newRowTemplate.parentNode.removeChild(newRowTemplate);
        views.decorateValidator(form);

        for (let row of tableBody.querySelectorAll('tr')) {
            this._addRemoveButtonClickHandler(row);
        }

        if (addLink) {
            addLink.addEventListener('click', e => {
                e.preventDefault();
                let newRow = newRowTemplate.cloneNode(true);
                tableBody.appendChild(newRow);
                this._addRemoveButtonClickHandler(newRow);
            });
        }

        form.addEventListener('submit', e => {
            this._saveButtonClickHandler(e, ctx, target);
        });

        views.listenToMessages(source);
        views.showView(target, source);
    }
}

module.exports = TagListHeaderView;
