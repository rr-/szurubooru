'use strict';

class CommentsController {
    constructor(topNavigationController) {
        this.topNavigationController = topNavigationController;
    }

    listCommentsRoute() {
        this.topNavigationController.activate('comments');
    }
}

module.exports = CommentsController;
