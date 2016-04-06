'use strict';

const page = require('page');
const topNavController = require('../controllers/top_nav_controller.js');

class PostsController {
    registerRoutes() {
        page('/upload', (ctx, next) => { this.uploadPostsRoute(); });
        page('/posts', (ctx, next) => { this.listPostsRoute(); });
        page(
            '/post/:id',
            (ctx, next) => { this.showPostRoute(ctx.params.id); });
        page(
            '/post/:id/edit',
            (ctx, next) => { this.editPostRoute(ctx.params.id); });
    }

    uploadPostsRoute() {
        topNavController.activate('upload');
    }

    listPostsRoute() {
        topNavController.activate('posts');
    }

    showPostRoute(id) {
        topNavController.activate('posts');
    }

    editPostRoute(id) {
        topNavController.activate('posts');
    }
}

module.exports = new PostsController();
