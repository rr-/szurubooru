'use strict';

require('./util/polyfill.js');

const page = require('page');
const mousetrap = require('mousetrap');
page(/.*/, (ctx, next) => {
    mousetrap.reset();
    next();
});

let controllers = [];
controllers.push(require('./controllers/auth_controller.js'));
controllers.push(require('./controllers/posts_controller.js'));
controllers.push(require('./controllers/users_controller.js'));
controllers.push(require('./controllers/help_controller.js'));
controllers.push(require('./controllers/comments_controller.js'));
controllers.push(require('./controllers/history_controller.js'));
controllers.push(require('./controllers/tags_controller.js'));
controllers.push(require('./controllers/settings_controller.js'));

controllers.push(require('./controllers/home_controller.js'));

const events = require('./events.js');
for (let controller of controllers) {
    controller.registerRoutes();
}

const api = require('./api.js');
api.loginFromCookies().then(() => {
    page();
}).catch(errorMessage => {
    if (window.location.href.indexOf('login') !== -1) {
        api.forget();
        page();
    } else {
        page('/');
        events.notify(
            events.Error,
            'An error happened while trying to log you in: ' + errorMessage);
    }
});
