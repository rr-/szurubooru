'use strict';

const router = require('../router.js');
const topNavController = require('../controllers/top_nav_controller.js');

class HistoryController {
    registerRoutes() {
        router.enter(
            '/history',
            (ctx, next) => { this._listHistoryRoute(); });
    }

    _listHistoryRoute() {
        topNavController.activate('');
    }
}

module.exports = new HistoryController();
