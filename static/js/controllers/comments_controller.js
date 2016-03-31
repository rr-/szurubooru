'use strict';

const topNavController = require('../controllers/top_nav_controller.js');

class CommentsController {
    listCommentsRoute() {
        topNavController.activate('comments');
    }
}

module.exports = new CommentsController();
