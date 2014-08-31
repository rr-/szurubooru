var App = App || {};

App.Router = function(jQuery) {

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

	function changePresenter(presenterGetter) {
		jQuery('#content').empty();
		var presenter = presenterGetter();
	};

	function injectRoutes() {
		inject('#/login', function() { return new App.DI.get('loginPresenter'); });
		inject('#/logout', function() { return new App.DI.get('logoutPresenter'); });
		inject('#/register', function() { return new App.DI.get('registrationPresenter'); });
		inject('#/users', function() { return App.DI.get('userListPresenter'); });
		setRoot('#/users');
	};

	function setRoot(newRoot) {
		root = newRoot;
		Path.root(newRoot);
	};

	function inject(path, presenterGetter) {
		Path.map(path).to(function() {
			changePresenter(presenterGetter);
		});
	};

	return {
		start: start,
		navigate: navigate,
		navigateToMainPage: navigateToMainPage,
	};

};

App.DI.registerSingleton('router', App.Router);
