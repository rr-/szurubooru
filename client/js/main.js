'use strict';

// ----------------------
// - import controllers -
// ----------------------
const homeController = require('./controllers/home_controller.js');
const postsController = require('./controllers/posts_controller.js');
const usersController = require('./controllers/users_controller.js');
const helpController = require('./controllers/help_controller.js');
const authController = require('./controllers/auth_controller.js');
const commentsController = require('./controllers/comments_controller.js');
const historyController = require('./controllers/history_controller.js');
const tagsController = require('./controllers/tags_controller.js');

// -----------------
// - setup routing -
// -----------------
const page = require('page');

page('/', () => { homeController.indexRoute(); });

page('/upload', () => { postsController.uploadPostsRoute(); });
page('/posts', () => { postsController.listPostsRoute(); });
page('/post/:id', id => { postsController.showPostRoute(id); });
page('/post/:id/edit', id => { postsController.editPostRoute(id); });

page('/register', () => { usersController.createUserRoute(); });
page('/users', () => { usersController.listUsersRoute(); });

page(
    '/user/:name',
    (ctx, next) => {
        usersController.showUserRoute(ctx.params.name);
    });

page(
    '/user/:name/edit',
    (ctx, next) => {
        usersController.editUserRoute(ctx.params.name);
    });

page('/history', () => { historyController.showHistoryRoute(); });
page('/tags', () => { tagsController.listTagsRoute(); });
page('/comments', () => { commentsController.listCommentsRoute(); });
page('/login', () => { authController.loginRoute(); });
page('/logout', () => { authController.logoutRoute(); });

page(
    '/help/:section',
    (ctx, next) => {
        helpController.showHelpRoute(ctx.params.section);
    });
page('/help', () => { helpController.showHelpRoute(); });

page('*', () => { homeController.notFoundRoute(); });

page();
