var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.TagListPresenter = function(
	jQuery,
	topNavigationPresenter) {

	var $el = jQuery('#content');

	function init(args) {
		topNavigationPresenter.select('tags');
		render();
	}

	function render() {
		$el.html('Tag list placeholder');
	}

	return {
		init: init,
		render: render,
	};

};

App.DI.register('tagListPresenter', App.Presenters.TagListPresenter);
