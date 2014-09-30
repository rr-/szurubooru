var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.LoginPresenter = function(
	_,
	jQuery,
	util,
	promise,
	router,
	auth,
	topNavigationPresenter,
	messagePresenter) {

	var $el = jQuery('#content');
	var $messages;
	var template;
	var previousRoute;

	function init(args, loaded) {
		topNavigationPresenter.select('login');
		topNavigationPresenter.changeTitle('Login');
		previousRoute = args.previousRoute;
		promise.wait(util.promiseTemplate('login-form'))
			.then(function(html) {
				template = _.template(html);
				if (auth.isLoggedIn()) {
					finishLogin();
				} else {
					render();
					$el.find('input:eq(0)').focus();
				}
				loaded();
			});
	}

	function render() {
		$el.html(template());
		$el.find('form').submit(loginFormSubmitted);
		$messages = $el.find('.messages');
		$messages.width($el.find('form').width());
	}

	function loginFormSubmitted(e) {
		e.preventDefault();
		messagePresenter.hideMessages($messages);

		var userNameOrEmail = $el.find('[name=user]').val();
		var password = $el.find('[name=password]').val();
		var remember = $el.find('[name=remember]').is(':checked');

		if (userNameOrEmail.length === 0) {
			messagePresenter.showError($messages, 'User name cannot be empty.');
			return false;
		}

		if (password.length === 0) {
			messagePresenter.showError($messages, 'Password cannot be empty.');
			return false;
		}

		auth.loginFromCredentials(userNameOrEmail, password, remember)
			.then(function(response) {
				finishLogin();
			}).fail(function(response) {
				messagePresenter.showError($messages, response.json && response.json.error || response);
			});
	}

	function finishLogin() {
		if (previousRoute && !previousRoute.match(/logout|password-reset|activate|register/)) {
			router.navigate(previousRoute);
		} else {
			router.navigateToMainPage();
		}
	}

	return {
		init: init,
		render: render,
	};

};

App.DI.register('loginPresenter', ['_', 'jQuery', 'util', 'promise', 'router', 'auth', 'topNavigationPresenter', 'messagePresenter'], App.Presenters.LoginPresenter);
