var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.UserPresenter = function(jQuery, topNavigationPresenter, appState) {

	var $el = jQuery('#content');
	var userName;

	function init(args) {
		userName = args.userName;
		topNavigationPresenter.select(appState.get('loggedIn') && appState.get('loggedInUser').name == userName ? 'my-account' : 'users');
		render();
	}

	function render() {
		$el.html('Viewing user: ' + userName);
	};

	return {
		init: init,
		render: render
	};

};

App.DI.register('userPresenter', App.Presenters.UserPresenter);
