'use strict';

const router = require('../router.js');
const api = require('../api.js');
const tags = require('../tags.js');
const Tag = require('../models/tag.js');
const topNavigation = require('../models/top_navigation.js');
const TagView = require('../views/tag_view.js');
const EmptyView = require('../views/empty_view.js');

class TagController {
    constructor(ctx, section) {
        Tag.get(ctx.params.name).then(tag => {
            topNavigation.activate('tags');

            this._name = ctx.params.name;
            tag.addEventListener('change', e => this._evtSaved(e));

            const categories = {};
            for (let category of tags.getAllCategories()) {
                categories[category.name] = category.name;
            }

            this._view = new TagView({
                tag: tag,
                section: section,
                canEditNames: api.hasPrivilege('tags:edit:names'),
                canEditCategory: api.hasPrivilege('tags:edit:category'),
                canEditImplications: api.hasPrivilege('tags:edit:implications'),
                canEditSuggestions: api.hasPrivilege('tags:edit:suggestions'),
                canEditDescription: api.hasPrivilege('tags:edit:description'),
                canMerge: api.hasPrivilege('tags:delete'),
                canDelete: api.hasPrivilege('tags:merge'),
                categories: categories,
            });

            this._view.addEventListener('change', e => this._evtChange(e));
            this._view.addEventListener('merge', e => this._evtMerge(e));
            this._view.addEventListener('delete', e => this._evtDelete(e));
        }, errorMessage => {
            this._view = new EmptyView();
            this._view.showError(errorMessage);
        });
    }

    _evtSaved(e) {
        if (this._name !== e.detail.tag.names[0]) {
            router.replace('/tag/' + e.detail.tag.names[0], null, false);
        }
    }

    _evtChange(e) {
        this._view.clearMessages();
        this._view.disableForm();
        e.detail.tag.names = e.detail.names;
        e.detail.tag.category = e.detail.category;
        e.detail.tag.implications = e.detail.implications;
        e.detail.tag.suggestions = e.detail.suggestions;
        e.detail.tag.description = e.detail.description;
        e.detail.tag.save().then(() => {
            this._view.showSuccess('Tag saved.');
            this._view.enableForm();
        }, response => {
            this._view.showError(response.description);
            this._view.enableForm();
        });
    }

    _evtMerge(e) {
        this._view.clearMessages();
        this._view.disableForm();
        e.detail.tag.merge(e.detail.targetTagName).then(() => {
            this._view.showSuccess('Tag merged.');
            this._view.enableForm();
        }, errorMessage => {
            this._view.showError(errorMessage);
            this._view.enableForm();
        });
    }

    _evtDelete(e) {
        this._view.clearMessages();
        this._view.disableForm();
        e.detail.tag.delete()
            .then(() => {
                const ctx = router.show('/tags/');
                ctx.controller.showSuccess('Tag deleted.');
            }, errorMessage => {
                this._view.showError(errorMessage);
                this._view.enableForm();
            });
    }
}

module.exports = router => {
    router.enter('/tag/:name', (ctx, next) => {
        ctx.controller = new TagController(ctx, 'summary');
    });
    router.enter('/tag/:name/edit', (ctx, next) => {
        ctx.controller = new TagController(ctx, 'edit');
    });
    router.enter('/tag/:name/merge', (ctx, next) => {
        ctx.controller = new TagController(ctx, 'merge');
    });
    router.enter('/tag/:name/delete', (ctx, next) => {
        ctx.controller = new TagController(ctx, 'delete');
    });
};
