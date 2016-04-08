'use strict';

require('./util/polyfill.js');
require('./util/handlebars-helpers.js');

let controllers = [];
controllers.push(require('./controllers/auth_controller.js'));
controllers.push(require('./controllers/posts_controller.js'));
controllers.push(require('./controllers/users_controller.js'));
controllers.push(require('./controllers/help_controller.js'));
controllers.push(require('./controllers/comments_controller.js'));
controllers.push(require('./controllers/history_controller.js'));
controllers.push(require('./controllers/tags_controller.js'));

controllers.push(require('./controllers/home_controller.js'));

const events = require('./events.js');
const page = require('page');
for (let controller of controllers) {
    controller.registerRoutes();
}

const api = require('./api.js');
api.loginFromCookies().then(() => {
    page();
}).catch(errorMessage => {
    page();
    page('/');
    events.notify(
        events.Error,
        'An error happened while trying to log you in: ' + errorMessage);
});
