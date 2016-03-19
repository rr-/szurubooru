'use strict';

class PostsController {
    constructor(topNavigationController) {
        this.topNavigationController = topNavigationController;
    }

    uploadPostsRoute() {
        this.topNavigationController.activate('upload');
    }

    listPostsRoute() {
        this.topNavigationController.activate('posts');
    }

    showPostRoute(id) {
        this.topNavigationController.activate('posts');
    }

    editPostRoute(id) {
        this.topNavigationController.activate('posts');
    }
}

module.exports = PostsController;
