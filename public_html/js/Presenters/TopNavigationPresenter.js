var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.TopNavigationPresenter = function(
	jQuery,
	util,
	promise,
	appState) {

	var selectedElement = null;
	var $el = jQuery('#top-navigation');
	var template;

	function init() {
		promise.wait(util.promiseTemplate('top-navigation')).then(function(html) {
			template = _.template(html);
			render();
			appState.startObserving('loggedIn', 'top-navigation', loginStateChanged);
		});
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
		$el.html(template({
			loggedIn: appState.get('loggedIn'),
			user: appState.get('loggedInUser'),
			privileges: appState.get('privileges'),
		}));
		$el.find('li.' + selectedElement).addClass('active');
	};

	return {
		init: init,
		render: render,
		select: select,
	};

};

App.DI.registerSingleton('topNavigationPresenter', App.Presenters.TopNavigationPresenter);
