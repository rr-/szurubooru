var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.LoginPresenter = function(
	jQuery,
	topNavigationPresenter,
	messagePresenter,
	auth,
	router,
	appState) {

	topNavigationPresenter.select('login');

	var $el = jQuery('#content');
	var $messages;
	var template = _.template(jQuery('#login-form-template').html());

	var eventHandlers = {

		loginFormSubmit: function(e) {
			e.preventDefault();
			messagePresenter.hideMessages($messages);

			var userName = $el.find('[name=user]').val();
			var password = $el.find('[name=password]').val();
			var remember = $el.find('[name=remember]').val();

			//todo: client side error reporting

			auth.loginFromCredentials(userName, password, remember)
				.then(function(response) {
					router.navigateToMainPage();
					//todo: "redirect" to main page
				}).catch(function(response) {
					messagePresenter.showError($messages, response.json && response.json.error || response);
				});
		},

	};

	if (appState.get('loggedIn'))
		router.navigateToMainPage();

	render();

	function render() {
		$el.html(template());
		$el.find('form').submit(eventHandlers.loginFormSubmit);
		$messages = $el.find('.messages');
		$messages.width($el.find('form').width());
	};

	return {
		render: render,
	};

};

App.DI.register('loginPresenter', App.Presenters.LoginPresenter);
