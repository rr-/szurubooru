'use strict';

const router = require('../router.js');
const TopNavigation = require('../models/top_navigation.js');
const EmptyView = require('../views/empty_view.js');

class PostUploadController {
    constructor() {
        this._emptyView = new EmptyView();
    }

    registerRoutes() {
        router.enter(
            '/upload',
            (ctx, next) => { this._uploadPostsRoute(); });
    }

    _uploadPostsRoute() {
        TopNavigation.activate('upload');
        this._emptyView.render();
    }
}

module.exports = new PostUploadController();
