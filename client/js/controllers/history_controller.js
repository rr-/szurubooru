'use strict';

const router = require('../router.js');
const TopNavigation = require('../models/top_navigation.js');

class HistoryController {
    registerRoutes() {
        router.enter(
            '/history',
            (ctx, next) => { this._listHistoryRoute(); });
    }

    _listHistoryRoute() {
        TopNavigation.activate('');
    }
}

module.exports = new HistoryController();
