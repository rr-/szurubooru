"use strict";

const config = require("./config.js");

if (config.environment == "development") {
    var ws = new WebSocket("ws://" + location.hostname + ":8081");
    ws.addEventListener("open", function (event) {
        console.log("Live-reloading websocket connected.");
    });
    ws.addEventListener("message", (event) => {
        console.log(event);
        if (event.data == "reload") {
            location.reload();
        }
    });
}

require("./util/polyfill.js");
const misc = require("./util/misc.js");
const views = require("./util/views.js");
const router = require("./router.js");

history.scrollRestoration = "manual";

router.exit(null, (ctx, next) => {
    ctx.state.scrollX = window.scrollX;
    ctx.state.scrollY = window.scrollY;
    router.replace(router.url, ctx.state);
    if (misc.confirmPageExit()) {
        next();
    }
});

const mousetrap = require("mousetrap");
router.enter(null, (ctx, next) => {
    mousetrap.reset();
    next();
});

const tags = require("./tags.js");
const pools = require("./pools.js");
const api = require("./api.js");
const settings = require("./models/settings.js");

Promise.resolve()
    .then(() => api.fetchConfig())
    .then(
        () => {
            // register controller routes
            let controllers = [];
            controllers.push(require("./controllers/home_controller.js"));
            controllers.push(require("./controllers/help_controller.js"));
            controllers.push(require("./controllers/auth_controller.js"));
            controllers.push(
                require("./controllers/password_reset_controller.js")
            );
            controllers.push(require("./controllers/comments_controller.js"));
            controllers.push(require("./controllers/snapshots_controller.js"));
            controllers.push(
                require("./controllers/post_detail_controller.js")
            );
            controllers.push(require("./controllers/post_main_controller.js"));
            controllers.push(require("./controllers/post_list_controller.js"));
            controllers.push(
                require("./controllers/post_upload_controller.js")
            );
            controllers.push(require("./controllers/tag_controller.js"));
            controllers.push(require("./controllers/tag_list_controller.js"));
            controllers.push(
                require("./controllers/tag_categories_controller.js")
            );
            controllers.push(
                require("./controllers/pool_create_controller.js")
            );
            controllers.push(require("./controllers/pool_controller.js"));
            controllers.push(require("./controllers/pool_list_controller.js"));
            controllers.push(
                require("./controllers/pool_categories_controller.js")
            );
            controllers.push(require("./controllers/settings_controller.js"));
            controllers.push(require("./controllers/user_controller.js"));
            controllers.push(require("./controllers/user_list_controller.js"));
            controllers.push(
                require("./controllers/user_registration_controller.js")
            );

            // 404 controller needs to be registered last
            controllers.push(require("./controllers/not_found_controller.js"));

            for (let controller of controllers) {
                controller(router);
            }
        },
        (error) => {
            window.alert("Could not fetch basic configuration from server");
        }
    )
    .then(() => {
        if (settings.get().darkTheme) {
            document.body.classList.add("darktheme");
        }
    })
    .then(() => api.loginFromCookies())
    .then(
        () => {
            tags.refreshCategoryColorMap();
            pools.refreshCategoryColorMap();
            router.start();
        },
        (error) => {
            if (window.location.href.indexOf("login") !== -1) {
                api.forget();
                router.start();
            } else {
                const ctx = router.start("/");
                ctx.controller.showError(
                    "An error happened while trying to log you in: " +
                        error.message
                );
            }
        }
    );
