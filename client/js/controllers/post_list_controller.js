'use strict';

const api = require('../api.js');
const settings = require('../models/settings.js');
const misc = require('../util/misc.js');
const PostList = require('../models/post_list.js');
const topNavigation = require('../models/top_navigation.js');
const PageController = require('../controllers/page_controller.js');
const PostsHeaderView = require('../views/posts_header_view.js');
const PostsPageView = require('../views/posts_page_view.js');
const EmptyView = require('../views/empty_view.js');

const fields = [
    'id', 'thumbnailUrl', 'type',
    'score', 'favoriteCount', 'commentCount', 'tags', 'version'];

class PostListController {
    constructor(ctx) {
        if (!api.hasPrivilege('posts:list')) {
            this._view = new EmptyView();
            this._view.showError('You don\'t have privileges to view posts.');
            return;
        }

        topNavigation.activate('posts');
        topNavigation.setTitle('Listing posts');

        this._ctx = ctx;
        this._pageController = new PageController({
            parameters: ctx.parameters,
            getClientUrlForPage: page => {
                const parameters = Object.assign(
                    {}, ctx.parameters, {page: page});
                return '/posts/' + misc.formatUrlParameters(parameters);
            },
            requestPage: page => {
                return PostList.search(
                    this._decorateSearchQuery(ctx.parameters.query),
                    page, settings.get().postsPerPage, fields);
            },
            headerRenderer: headerCtx => {
                Object.assign(headerCtx, {
                    canMassTag: api.hasPrivilege('tags:masstag'),
                    massTagTags: this._massTagTags,
                });
                return new PostsHeaderView(headerCtx);
            },
            pageRenderer: pageCtx => {
                Object.assign(pageCtx, {
                    canViewPosts: api.hasPrivilege('posts:view'),
                    massTagTags: this._massTagTags,
                });
                const view = new PostsPageView(pageCtx);
                view.addEventListener('tag', e => this._evtTag(e));
                view.addEventListener('untag', e => this._evtUntag(e));
                return view;
            },
        });
    }

    showSuccess(message) {
        this._pageController.showSuccess(message);
    }

    get _massTagTags() {
        return (this._ctx.parameters.tag || '').split(/\s+/).filter(s => s);
    }

    _evtTag(e) {
        for (let tag of this._massTagTags) {
            e.detail.post.addTag(tag);
        }
        e.detail.post.save()
            .catch(errorMessage => {
                window.alert(errorMessage);
            });
    }

    _evtUntag(e) {
        for (let tag of this._massTagTags) {
            e.detail.post.removeTag(tag);
        }
        e.detail.post.save()
            .catch(errorMessage => {
                window.alert(errorMessage);
            });
    }

    _decorateSearchQuery(text) {
        const browsingSettings = settings.get();
        let disabledSafety = [];
        for (let key of Object.keys(browsingSettings.listPosts)) {
            if (browsingSettings.listPosts[key] === false) {
                disabledSafety.push(key);
            }
        }
        if (disabledSafety.length) {
            text = `-rating:${disabledSafety.join(',')} ${text}`;
        }
        return text.trim();
    }
}

module.exports = router => {
    router.enter(
        '/posts/:parameters?',
        (ctx, next) => { misc.parseUrlParametersRoute(ctx, next); },
        (ctx, next) => { ctx.controller = new PostListController(ctx); });
};
