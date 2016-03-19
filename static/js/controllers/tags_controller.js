'use strict';

class TagsController {
    constructor(topNavigationController) {
        this.topNavigationController = topNavigationController;
    }

    listTagsRoute() {
        this.topNavigationController.activate('tags');
    }
}

module.exports = TagsController;
