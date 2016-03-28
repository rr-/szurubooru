'use strict';

// ------------------
// - import objects -
// ------------------
const page = require('page');
const handlebars = require('handlebars');

const Api = require('./api.js');
const LoginView = require('./views/login_view.js');
const RegistrationView = require('./views/registration_view.js');
const TopNavigationView = require('./views/top_navigation_view.js');
const TopNavigationController
    = require('./controllers/top_navigation_controller.js');

const HomeController = require('./controllers/home_controller.js');
const PostsController = require('./controllers/posts_controller.js');
const UsersController = require('./controllers/users_controller.js');
const HelpController = require('./controllers/help_controller.js');
const AuthController = require('./controllers/auth_controller.js');
const CommentsController = require('./controllers/comments_controller.js');
const HistoryController = require('./controllers/history_controller.js');
const TagsController = require('./controllers/tags_controller.js');

// -------------------
// - resolve objects -
// -------------------
const api = new Api();

const topNavigationView = new TopNavigationView(handlebars);
const loginView = new LoginView(handlebars);
const registrationView = new RegistrationView(handlebars);

const authController = new AuthController(api, null, loginView);
const topNavigationController
    = new TopNavigationController(topNavigationView, authController);
// break cyclic dependency topNavigationView<->authController
authController.topNavigationController = topNavigationController;

const homeController = new HomeController(topNavigationController);
const postsController = new PostsController(topNavigationController);
const usersController = new UsersController(
    api,
    topNavigationController,
    authController,
    registrationView);
const helpController = new HelpController(topNavigationController);
const commentsController = new CommentsController(topNavigationController);
const historyController = new HistoryController(topNavigationController);
const tagsController = new TagsController(topNavigationController);

// -----------------
// - setup routing -
// -----------------
page('/', () => { homeController.indexRoute(); });

page('/upload', () => { postsController.uploadPostsRoute(); });
page('/posts', () => { postsController.listPostsRoute(); });
page('/post/:id', id => { postsController.showPostRoute(id); });
page('/post/:id/edit', id => { postsController.editPostRoute(id); });

page('/register', () => { usersController.createUserRoute(); });
page('/users', () => { usersController.listUsersRoute(); });
page('/user/:user', user => { usersController.showUserRoute(user); });
page('/user/:user/edit', user => { usersController.editUserRoute(user); });

page('/history', () => { historyController.showHistoryRoute(); });
page('/tags', () => { tagsController.listTagsRoute(); });
page('/comments', () => { commentsController.listCommentsRoute(); });
page('/login', () => { authController.loginRoute(); });
page('/logout', () => { authController.logoutRoute(); });
page('/help', () => { helpController.showHelpRoute(); });

page('*', () => { homeController.notFoundRoute(); });

page();
