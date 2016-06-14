'use strict';

const api = require('../api.js');
const tags = require('../tags.js');
const misc = require('../util/misc.js');
const topNavigation = require('../models/top_navigation.js');
const TagCategoriesView = require('../views/tag_categories_view.js');
const EmptyView = require('../views/empty_view.js');

class TagCategoriesController {
    constructor() {
        topNavigation.activate('tags');
        api.get('/tag-categories/').then(response => {
            this._view = new TagCategoriesView({
                tagCategories: response.results,
                canEditName: api.hasPrivilege('tagCategories:edit:name'),
                canEditColor: api.hasPrivilege('tagCategories:edit:color'),
                canDelete: api.hasPrivilege('tagCategories:delete'),
                canCreate: api.hasPrivilege('tagCategories:create'),
                canSetDefault: api.hasPrivilege('tagCategories:setDefault'),
                saveChanges: (...args) => {
                    return this._saveTagCategories(...args);
                },
                getCategories: () => {
                    return api.get('/tag-categories/').then(response => {
                        return Promise.resolve(response.results);
                    }, response => {
                        return Promise.reject(response);
                    });
                }
            });
        }, response => {
            this._view = new EmptyView();
            this._view.showError(response.description);
        });
    }

    _saveTagCategories(
            addedCategories,
            changedCategories,
            removedCategories,
            defaultCategory) {
        let promises = [];
        for (let category of addedCategories) {
            promises.push(api.post('/tag-categories/', category));
        }
        for (let category of changedCategories) {
            promises.push(
                api.put('/tag-category/' + category.originalName, category));
        }
        for (let name of removedCategories) {
            promises.push(api.delete('/tag-category/' + name));
        }
        Promise.all(promises)
            .then(
                () => {
                    if (!defaultCategory) {
                        return Promise.resolve();
                    }
                    return api.put(
                        '/tag-category/' + defaultCategory + '/default');
                }, response => {
                    return Promise.reject(response);
                })
            .then(
                () => {
                    tags.refreshExport();
                    this._view.showSuccess('Changes saved.');
                },
                response => {
                    this._view.showError(response.description);
                });
    }
}

module.exports = router => {
    router.enter('/tag-categories', (ctx, next) => {
        ctx.controller = new TagCategoriesController(ctx, next);
    });
};
