var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.LogoutPresenter = function(
	jQuery,
	topNavigationPresenter,
	messagePresenter,
	auth,
	router) {

	topNavigationPresenter.select('logout');

	var $messages = jQuery('#content');

	var eventHandlers = {
		mainPageLinkClick: function(e) {
			e.preventDefault();
			router.navigateToMainPage();
		},
	};

	auth.logout().then(function() {
		var $messageDiv = messagePresenter.showInfo($messages, 'Logged out. <a href="">Back to main page</a>');
		$messageDiv.find('a').click(eventHandlers.mainPageLinkClick);
	}).catch(function(response) {
		messagePresenter.showError($messages, response.json && response.json.error || response);
	});

	return {
	};

};

App.DI.register('logoutPresenter', App.Presenters.LogoutPresenter);
