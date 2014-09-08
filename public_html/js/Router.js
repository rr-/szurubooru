var App = App || {};

App.Router = function(jQuery, util, appState) {

	var root = '#/';

	injectRoutes();

	function navigateToMainPage() {
		window.location.href = root;
	};

	function navigate(url) {
		window.location.href = url;
	};

	function start() {
		Path.listen();
	};

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
	};

	function setRoot(newRoot) {
		root = newRoot;
		Path.root(newRoot);
	};

	function inject(path, presenterName, additionalParams) {
		Path.map(path).to(function() {
			util.initContentPresenter(presenterName, _.extend(this.params, additionalParams));
		});
	};

	return {
		start: start,
		navigate: navigate,
		navigateToMainPage: navigateToMainPage,
	};

};

App.DI.registerSingleton('router', App.Router);
