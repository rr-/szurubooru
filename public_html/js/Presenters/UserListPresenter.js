var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.UserListPresenter = function(jQuery, topNavigationPresenter, appState) {

	topNavigationPresenter.select('users');

	var $el = jQuery('#content');

	init();

	function init() {
		render();
	}

	function render() {
		$el.html('Logged in: ' + appState.get('loggedIn'));
	};

	return {
		render: render
	};

};

App.DI.register('userListPresenter', App.Presenters.UserListPresenter);
