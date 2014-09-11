var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.PostListPresenter = function(
	jQuery,
	topNavigationPresenter) {

	var $el = jQuery('#content');

	function init(args) {
		topNavigationPresenter.select('posts');
		render();
	}

	function render() {
		$el.html('Post list placeholder');
	}

	return {
		init: init,
		render: render,
	};

};

App.DI.register('postListPresenter', ['jQuery', 'topNavigationPresenter'], App.Presenters.PostListPresenter);
