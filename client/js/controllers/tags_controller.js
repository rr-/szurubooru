'use strict';

const page = require('page');
const api = require('../api.js');
const events = require('../events.js');
const misc = require('../util/misc.js');
const topNavController = require('../controllers/top_nav_controller.js');
const pageController = require('../controllers/page_controller.js');
const TagListHeaderView = require('../views/tag_list_header_view.js');
const TagListPageView = require('../views/tag_list_page_view.js');
const TagCategoriesView = require('../views/tag_categories_view.js');

class TagsController {
    constructor() {
        this.tagListHeaderView = new TagListHeaderView();
        this.tagListPageView = new TagListPageView();
        this.tagCategoriesView = new TagCategoriesView();
    }

    registerRoutes() {
        page('/tag-categories', () => { this.tagCategoriesRoute(); });
        page(
            '/tags/:query?',
            (ctx, next) => { misc.parseSearchQueryRoute(ctx, next); },
            (ctx, next) => { this.listTagsRoute(ctx, next); });
    }

    _saveTagCategories(addedCategories, changedCategories, removedCategories) {
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
        Promise.all(promises).then(
            () => {
                events.notify(events.TagsChange);
                events.notify(events.Success, 'Changes saved successfully');
            },
            response => {
                events.notify(events.Error, response.description);
            });
    }

    tagCategoriesRoute(ctx, next) {
        topNavController.activate('tags');
        api.get('/tag-categories/').then(response => {
            this.tagCategoriesView.render({
                tagCategories: response.results,
                canEditName: api.hasPrivilege('tagCategories:edit:name'),
                canEditColor: api.hasPrivilege('tagCategories:edit:color'),
                canDelete: api.hasPrivilege('tagCategories:delete'),
                canCreate: api.hasPrivilege('tagCategories:create'),
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
            this.emptyView.render();
            events.notify(events.Error, response.description);
        });
    }

    listTagsRoute(ctx, next) {
        topNavController.activate('tags');

        pageController.run({
            state: ctx.state,
            requestPage: page => {
                return api.get(
                    '/tags/?query={text}&page={page}&pageSize=50'.format({
                        text: ctx.searchQuery.text,
                        page: page}));
            },
            clientUrl: '/tags/' + misc.formatSearchQuery({
                text: ctx.searchQuery.text, page: '{page}'}),
            searchQuery: ctx.searchQuery,
            headerRenderer: this.tagListHeaderView,
            pageRenderer: this.tagListPageView,
            canEditTagCategories: api.hasPrivilege('tagCategories:edit'),
        });
    }
}

module.exports = new TagsController();
