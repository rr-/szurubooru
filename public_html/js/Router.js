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
		inject('#/login', 'loginPresenter');
		inject('#/logout', 'logoutPresenter');
		inject('#/register', 'registrationPresenter');
		inject('#/users', 'userListPresenter');
		inject('#/users/:searchArgs', 'userListPresenter');
		inject('#/user/:userName', 'userPresenter');
		inject('#/user/:userName/:tab', 'userPresenter');
		setRoot('#/users');
	};

	function setRoot(newRoot) {
		root = newRoot;
		Path.root(newRoot);
	};

	function inject(path, presenterName) {
		Path.map(path).to(function() {
			util.initContentPresenter(presenterName, this.params);
		});
	};

	return {
		start: start,
		navigate: navigate,
		navigateToMainPage: navigateToMainPage,
	};

};

App.DI.registerSingleton('router', App.Router);
