var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.HomePresenter = function(
	jQuery,
	topNavigationPresenter) {

	var $el = jQuery('#content');

	function init(args) {
		topNavigationPresenter.select('home');
		render();
	}

	function render() {
		$el.html('Home placeholder');
	}

	return {
		init: init,
		render: render,
	};

};

App.DI.register('homePresenter', ['jQuery', 'topNavigationPresenter'], App.Presenters.HomePresenter);
