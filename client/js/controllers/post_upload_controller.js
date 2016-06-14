'use strict';

const topNavigation = require('../models/top_navigation.js');
const EmptyView = require('../views/empty_view.js');

class PostUploadController {
    constructor() {
        topNavigation.activate('upload');
        this._emptyView = new EmptyView();
    }
}

module.exports = router => {
    router.enter('/upload', (ctx, next) => {
        ctx.controller = new PostUploadController();
    });
};
