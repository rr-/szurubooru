'use strict';

const page = require('page');
const topNavController = require('../controllers/top_nav_controller.js');

class CommentsController {
    registerRoutes() {
        page('/comments', (ctx, next) => { this.listCommentsRoute(); });
    }

    listCommentsRoute() {
        topNavController.activate('comments');
    }
}

module.exports = new CommentsController();
