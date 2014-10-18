var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.HttpErrorPresenter = function(
	jQuery,
	promise,
	util,
	topNavigationPresenter) {

	var $el = jQuery('#content');
	var templates = {};

	function init(params, loaded) {
		topNavigationPresenter.changeTitle('Error ' + params.error);

		if (params.error === 404) {
			promise.wait(util.promiseTemplate('404'))
				.then(function(template) {
					templates.errorPage = template;
					reinit(params, loaded);
				}).fail(function() {
					console.log(arguments);
					loaded();
				});
		} else {
			console.log('Not supported.');
			loaded();
		}
	}

	function reinit(params, loaded) {
		render();
		loaded();
	}

	function render() {
		$el.html(templates.errorPage());
	}

	return {
		init: init,
		reinit: reinit,
		render: render,
	};

};

App.DI.register('httpErrorPresenter', ['jQuery', 'promise', 'util', 'topNavigationPresenter'], App.Presenters.HttpErrorPresenter);
