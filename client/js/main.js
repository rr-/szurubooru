'use strict';

require('./util/polyfill.js');
const misc = require('./util/misc.js');

const router = require('./router.js');

history.scrollRestoration = 'manual';

router.exit(
    /.*/,
    (ctx, next) => {
        ctx.state.scrollX = window.scrollX;
        ctx.state.scrollY = window.scrollY;
        ctx.save();
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
        window.requestAnimationFrame(
            () => {
                window.scrollTo(
                    ctx.state.scrollX || 0,
                    ctx.state.scrollY || 0);
            });
    });

let controllers = [];
controllers.push(require('./controllers/auth_controller.js'));
controllers.push(require('./controllers/post_list_controller.js'));
controllers.push(require('./controllers/post_upload_controller.js'));
controllers.push(require('./controllers/post_controller.js'));
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
