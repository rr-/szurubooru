'use strict';

require('./util/polyfill.js');
const misc = require('./util/misc.js');

const router = require('./router.js');

const origPushState = router.Context.prototype.pushState;
router.Context.prototype.pushState = function() {
    window.scrollTo(0, 0);
    origPushState.call(this);
};

router.exit(
    /.*/,
    (ctx, next) => {
        views.unlistenToMessages();
        if (misc.confirmPageExit()) {
            next();
        }
    });

const mousetrap = require('mousetrap');
router.enter(
    /.*/,
    (ctx, next) => {
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

// home defines 404 routes, need to be registered as last
controllers.push(require('./controllers/home_controller.js'));

const tags = require('./tags.js');
const events = require('./events.js');
const views = require('./util/views.js');
for (let controller of controllers) {
    controller.registerRoutes();
}

const api = require('./api.js');
Promise.all([tags.refreshExport(), api.loginFromCookies()])
    .then(() => {
        router.start();
    }).catch(errorMessage => {
        if (window.location.href.indexOf('login') !== -1) {
            api.forget();
            router.start();
        } else {
            router.start('/');
            events.notify(
                events.Error,
                'An error happened while trying to log you in: ' +
                    errorMessage);
        }
    });
