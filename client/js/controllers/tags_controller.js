'use strict';

const page = require('page');
const topNavController = require('../controllers/top_nav_controller.js');

class TagsController {
    registerRoutes() {
        page('/tags', (ctx, next) => { this.listTagsRoute(); });
    }

    listTagsRoute() {
        topNavController.activate('tags');
    }
}

module.exports = new TagsController();
