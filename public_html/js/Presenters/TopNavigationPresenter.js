var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.TopNavigationPresenter = function(util, jQuery, appState) {

	var selectedElement = null;
	var $el = jQuery('#top-navigation');
	var template;

	util.loadTemplate('top-navigation').then(function(html) {
		template = _.template(html);
		init();
	});

	function init() {
		render();
		appState.startObserving('loggedIn', 'top-navigation', loginStateChanged);
	}

	function select(newSelectedElement) {
		selectedElement = newSelectedElement;
		$el.find('li').removeClass('active');
		$el.find('li.' + selectedElement).addClass('active');
	};

	function loginStateChanged() {
		render();
	}

	function render() {
		$el.html(template({loggedIn: appState.get('loggedIn')}));
		$el.find('li.' + selectedElement).addClass('active');
	};

	return {
		render: render,
		select: select,
	};

};

App.DI.register('topNavigationPresenter', App.Presenters.TopNavigationPresenter);
