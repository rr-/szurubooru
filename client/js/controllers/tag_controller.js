'use strict';

const router = require('../router.js');
const api = require('../api.js');
const tags = require('../tags.js');
const topNavigation = require('../models/top_navigation.js');
const TagView = require('../views/tag_view.js');
const EmptyView = require('../views/empty_view.js');

class TagController {
    constructor(ctx, section) {
        new Promise((resolve, reject) => {
            if (ctx.state.tag) {
                resolve(ctx.state.tag);
                return;
            }
            api.get('/tag/' + ctx.params.name).then(response => {
                ctx.state.tag = response;
                ctx.save();
                resolve(ctx.state.tag);
            }, response => {
                reject(response.description);
            });
        }).then(tag => {
            topNavigation.activate('tags');

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

    _evtChange(e) {
        this._view.clearMessages();
        this._view.disableForm();
        return api.put('/tag/' + e.detail.tag.names[0], {
            names: e.detail.names,
            category: e.detail.category,
            implications: e.detail.implications,
            suggestions: e.detail.suggestions,
        }).then(response => {
            // TODO: update header links and text
            if (e.detail.names && e.detail.names[0] !== e.detail.tag.names[0]) {
                router.replace('/tag/' + e.detail.names[0], null, false);
            }
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
        return api.post(
            '/tag-merge/',
            {remove: e.detail.tag.names[0], mergeTo: e.detail.targetTagName}
        ).then(response => {
            // TODO: update header links and text
            router.replace(
                '/tag/' + e.detail.targetTagName + '/merge', null, false);
            this._view.showSuccess('Tag merged.');
            this._view.enableForm();
        }, response => {
            this._view.showError(response.description);
            this._view.enableForm();
        });
    }

    _evtDelete(e) {
        this._view.clearMessages();
        this._view.disableForm();
        return api.delete('/tag/' + e.detail.tag.names[0]).then(response => {
            const ctx = router.show('/tags/');
            ctx.controller.showSuccess('Tag deleted.');
        }, response => {
            this._view.showError(response.description);
            this._view.enableForm();
        });
    }
}

module.exports = router => {
    router.enter('/tag/:name', (ctx, next) => {
        ctx.controller = new TagController(ctx, 'summary');
    });
    router.enter('/tag/:name/merge', (ctx, next) => {
        ctx.controller = new TagController(ctx, 'merge');
    });
    router.enter('/tag/:name/delete', (ctx, next) => {
        ctx.controller = new TagController(ctx, 'delete');
    });
};
