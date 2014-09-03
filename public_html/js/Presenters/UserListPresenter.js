var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.UserListPresenter = function(jQuery, topNavigationPresenter, appState) {

	var $el = jQuery('#content');

	function init() {
		topNavigationPresenter.select('users');
		render();
	}

	function render() {
		$el.html('Logged in: ' + appState.get('loggedIn'));
	};

	return {
		init: init,
		render: render
	};

};

App.DI.register('userListPresenter', App.Presenters.UserListPresenter);
