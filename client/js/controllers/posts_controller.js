'use strict';

const page = require('page');
const topNavController = require('../controllers/top_nav_controller.js');

class PostsController {
    registerRoutes() {
        page('/upload', (ctx, next) => { this._uploadPostsRoute(); });
        page('/posts', (ctx, next) => { this._listPostsRoute(); });
        page(
            '/post/:id',
            (ctx, next) => { this._showPostRoute(ctx.params.id); });
        page(
            '/post/:id/edit',
            (ctx, next) => { this._editPostRoute(ctx.params.id); });
    }

    _uploadPostsRoute() {
        topNavController.activate('upload');
    }

    _listPostsRoute() {
        topNavController.activate('posts');
    }

    _showPostRoute(id) {
        topNavController.activate('posts');
    }

    _editPostRoute(id) {
        topNavController.activate('posts');
    }
}

module.exports = new PostsController();
