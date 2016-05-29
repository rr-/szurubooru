'use strict';

const misc = require('../util/misc.js');
const page = require('page');
const topNavController = require('../controllers/top_nav_controller.js');
const EmptyView = require('../views/empty_view.js');

class PostsController {
    registerRoutes() {
        page('/upload', (ctx, next) => { this._uploadPostsRoute(); });
        page('/posts/:query?',
            (ctx, next) => { misc.parseSearchQueryRoute(ctx, next); },
            (ctx, next) => { this._listPostsRoute(); });
        page(
            '/post/:id',
            (ctx, next) => { this._showPostRoute(ctx.params.id); });
        page(
            '/post/:id/edit',
            (ctx, next) => { this._editPostRoute(ctx.params.id); });
        this._emptyView = new EmptyView();
    }

    _uploadPostsRoute() {
        topNavController.activate('upload');
        this._emptyView.render();
    }

    _listPostsRoute() {
        topNavController.activate('posts');
        this._emptyView.render();
    }

    _showPostRoute(id) {
        topNavController.activate('posts');
        this._emptyView.render();
    }

    _editPostRoute(id) {
        topNavController.activate('posts');
        this._emptyView.render();
    }
}

module.exports = new PostsController();
