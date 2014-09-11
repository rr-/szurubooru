var App = App || {};

App.Router = function(pathJs, _, jQuery, util, appState) {

	var root = '#/';

	injectRoutes();

	function navigateToMainPage() {
		window.location.href = root;
	}

	function navigate(url) {
		window.location.href = url;
	}

	function start() {
		pathJs.listen();
	}

	function injectRoutes() {
		inject('#/home', 'homePresenter');
		inject('#/login', 'loginPresenter');
		inject('#/logout', 'logoutPresenter');
		inject('#/register', 'registrationPresenter');
		inject('#/upload', 'postUploadPresenter');
		inject('#/password-reset(/:token)', 'userActivationPresenter', {operation: 'passwordReset'});
		inject('#/activate(/:token)', 'userActivationPresenter', {operation: 'activation'});
		inject('#/users(/:searchArgs)', 'userListPresenter');
		inject('#/user/:userName(/:tab)', 'userPresenter');
		inject('#/posts(/:searchArgs)', 'postListPresenter');
		inject('#/comments(/:searchArgs)', 'commentListPresenter');
		inject('#/tags(/:searchArgs)', 'tagListPresenter');
		inject('#/help', 'helpPresenter');
		setRoot('#/home');
	}

	function setRoot(newRoot) {
		root = newRoot;
		pathJs.root(newRoot);
	}

	function inject(path, presenterName, additionalParams) {
		pathJs.map(path).to(function() {
			var finalParams = _.extend(
				this.params,
				additionalParams,
				{previousRoute: pathJs.routes.previous});

			util.initContentPresenter( presenterName, finalParams);
		});
	}

	return {
		start: start,
		navigate: navigate,
		navigateToMainPage: navigateToMainPage,
	};

};

App.DI.registerSingleton('router', ['pathJs', '_', 'jQuery', 'util', 'appState'], App.Router);
