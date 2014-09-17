var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.HelpPresenter = function(
	jQuery,
	topNavigationPresenter) {

	var $el = jQuery('#content');

	function init(args, loaded) {
		topNavigationPresenter.select('help');
		topNavigationPresenter.changeTitle('Help');
		render();
		loaded();
	}

	function render() {
		$el.html('Help placeholder');
	}

	return {
		init: init,
		render: render,
	};

};

App.DI.register('helpPresenter', ['jQuery', 'topNavigationPresenter'], App.Presenters.HelpPresenter);
