var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.PostUploadPresenter = function(
	jQuery,
	topNavigationPresenter) {

	var $el = jQuery('#content');

	function init(args) {
		topNavigationPresenter.select('upload');
		topNavigationPresenter.changeTitle('Upload');
		render();
	}

	function render() {
		$el.html('Post upload placeholder');
	}

	return {
		init: init,
		render: render,
	};

};

App.DI.register('postUploadPresenter', ['jQuery', 'topNavigationPresenter'], App.Presenters.PostUploadPresenter);
