var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.HomePresenter = function(
	jQuery,
	topNavigationPresenter) {

	var $el = jQuery('#content');

	function init(args, loaded) {
		topNavigationPresenter.select('home');
		topNavigationPresenter.changeTitle('Home');
		render();
		loaded();
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
