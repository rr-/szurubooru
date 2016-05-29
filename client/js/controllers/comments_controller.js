'use strict';

const page = require('page');
const topNavController = require('../controllers/top_nav_controller.js');
const EmptyView = require('../views/empty_view.js');

class CommentsController {
    registerRoutes() {
        page('/comments', (ctx, next) => { this._listCommentsRoute(); });
        this._emptyView = new EmptyView();
    }

    _listCommentsRoute() {
        topNavController.activate('comments');
        this._emptyView.render();
    }
}

module.exports = new CommentsController();
