var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.UserActivationPresenter = function(
	jQuery,
	topNavigationPresenter) {

	var $el = jQuery('#content');

	function init(args) {
		topNavigationPresenter.select('login');
		render();
	}

	function render() {
		$el.html('Account activation placeholder');
	};

	return {
		init: init,
		render: render,
	};

};

App.DI.register('userActivationPresenter', App.Presenters.UserActivationPresenter);
