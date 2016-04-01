'use strict';

const topNavController = require('../controllers/top_nav_controller.js');

class TagsController {
    listTagsRoute() {
        topNavController.activate('tags');
    }
}

module.exports = new TagsController();
