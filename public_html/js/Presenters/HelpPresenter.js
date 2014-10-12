var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.HelpPresenter = function(
	jQuery,
	promise,
	util,
	topNavigationPresenter) {

	var $el = jQuery('#content');
	var templates = {};
	var activeTab;

	function init(params, loaded) {
		topNavigationPresenter.select('help');
		topNavigationPresenter.changeTitle('Help');

		promise.wait(util.promiseTemplate('help'))
			.then(function(template) {
				templates.help = template;
				reinit(params, loaded);
			}).fail(function() {
				console.log(arguments);
				loaded();
			});
	}

	function reinit(params, loaded) {
		activeTab = params.tab || 'about';
		render();
		loaded();
	}

	function render() {
		$el.html(templates.help({title: topNavigationPresenter.getBaseTitle() }));
		$el.find('.big-button').removeClass('active');
		$el.find('.big-button[href*="' + activeTab + '"]').addClass('active');
		$el.find('div[data-tab]').hide();
		$el.find('div[data-tab*="' + activeTab + '"]').show();
	}

	return {
		init: init,
		reinit: reinit,
		render: render,
	};

};

App.DI.register('helpPresenter', ['jQuery', 'promise', 'util', 'topNavigationPresenter'], App.Presenters.HelpPresenter);
