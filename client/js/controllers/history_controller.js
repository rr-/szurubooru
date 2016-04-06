'use strict';

const page = require('page');
const topNavController = require('../controllers/top_nav_controller.js');

class HistoryController {
    registerRoutes() {
        page('/history', (ctx, next) => { this.showHistoryRoute(); });
    }

    listHistoryRoute() {
        topNavController.activate('');
    }
}

module.exports = new HistoryController();
