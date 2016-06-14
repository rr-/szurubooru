'use strict';

const topNavigation = require('../models/top_navigation.js');

class HistoryController {
    constructor() {
        topNavigation.activate('');
    }
}

module.exports = router => {
    router.enter('/history', (ctx, next) => {
        ctx.controller = new HistoryController();
    });
};
