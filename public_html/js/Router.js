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
		inject('#/login', function() { return App.DI.get('loginPresenter'); });
		inject('#/logout', function() { return App.DI.get('logoutPresenter'); });
		inject('#/register', function() { return App.DI.get('registrationPresenter'); });
		inject('#/users', function() { return App.DI.get('userListPresenter'); });
		inject('#/users/:searchArgs', function() { return App.DI.get('userListPresenter'); });
		inject('#/user/:userName', function() { return App.DI.get('userPresenter'); });
		setRoot('#/users');
	};

	function setRoot(newRoot) {
		root = newRoot;
		Path.root(newRoot);
	};

	function inject(path, presenterGetter) {
		Path.map(path).to(function() {
			util.initContentPresenter(presenterGetter, this.params);
		});
	};

	return {
		start: start,
		navigate: navigate,
		navigateToMainPage: navigateToMainPage,
	};

};

App.DI.registerSingleton('router', App.Router);
