'use strict';

const topNavController = require('../controllers/top_nav_controller.js');

class PostsController {
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
