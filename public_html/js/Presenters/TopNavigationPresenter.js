var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.TopNavigationPresenter = function(util, jQuery, appState) {

	var selectedElement = null;
	var template;

	util.loadTemplate('top-navigation').then(function(html) {
		template = _.template(html);
		render();
	});
	var $el = jQuery('#top-navigation');

	var eventHandlers = {
		loginStateChanged: function() {
			render();
		},
	};

	appState.startObserving('loggedIn', 'top-navigation', eventHandlers.loginStateChanged);

	function select(newSelectedElement) {
		selectedElement = newSelectedElement;
		$el.find('li').removeClass('active');
		$el.find('li.' + selectedElement).addClass('active');
	};

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
