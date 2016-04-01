'use strict';

const topNavController = require('../controllers/top_nav_controller.js');

class HistoryController {
    listHistoryRoute() {
        topNavController.activate('');
    }
}

module.exports = new HistoryController();
