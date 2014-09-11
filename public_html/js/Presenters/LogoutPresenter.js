var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.LogoutPresenter = function(
	jQuery,
	promise,
	router,
	auth,
	topNavigationPresenter,
	messagePresenter) {

	var $messages = jQuery('#content');

	function init() {
		topNavigationPresenter.select('logout');
		topNavigationPresenter.changeTitle('Logout');
		promise.wait(auth.logout()).then(function() {
			$messages.empty();
			var $messageDiv = messagePresenter.showInfo($messages, 'Logged out. <a href="">Back to main page</a>');
			$messageDiv.find('a').click(mainPageLinkClicked);
		}).fail(function(response) {
			messagePresenter.showError(($messages, response.json && response.json.error || response) + '<br/>Reload the page to continue.');
		});
	}

	function mainPageLinkClicked(e) {
		e.preventDefault();
		router.navigateToMainPage();
	}

	return {
		init: init
	};

};

App.DI.register('logoutPresenter', ['jQuery', 'promise', 'router', 'auth', 'topNavigationPresenter', 'messagePresenter'], App.Presenters.LogoutPresenter);
