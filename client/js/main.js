'use strict';

require('./util/handlebars-helpers.js');

let controllers = [];
controllers.push(require('./controllers/posts_controller.js'));
controllers.push(require('./controllers/users_controller.js'));
controllers.push(require('./controllers/help_controller.js'));
controllers.push(require('./controllers/auth_controller.js'));
controllers.push(require('./controllers/comments_controller.js'));
controllers.push(require('./controllers/history_controller.js'));
controllers.push(require('./controllers/tags_controller.js'));

controllers.push(require('./controllers/home_controller.js'));

const page = require('page');
for (let controller of controllers) {
    controller.registerRoutes();
}
page();
