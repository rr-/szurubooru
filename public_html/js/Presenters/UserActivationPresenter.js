var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.UserActivationPresenter = function(
	jQuery,
	promise,
	util,
	auth,
	api,
	router,
	topNavigationPresenter,
	messagePresenter) {

	var $el = jQuery('#content');
	var $messages = $el;
	var templates = {};
	var formHidden = false;
	var operation;

	function init(args, loaded) {
		topNavigationPresenter.select('login');
		topNavigationPresenter.changeTitle('Account recovery');
		reinit(args, loaded);
	}

	function reinit(args, loaded) {
		operation = args.operation;
		promise.wait(util.promiseTemplate('user-query-form'))
			.then(function(template) {
				templates.userQuery = template;
				if (args.token) {
					hideForm();
					confirmToken(args.token);
				} else {
					showForm();
				}
				render();
				loaded();
			});
	}

	function render() {
		$el.html(templates.userQuery());
		$messages = $el.find('.messages');
		if (formHidden) {
			$el.find('form').hide();
		}
		$el.find('form').submit(userQueryFormSubmitted);
	}

	function hideForm() {
		formHidden = true;
	}

	function showForm() {
		formHidden = false;
	}

	function userQueryFormSubmitted(e) {
		e.preventDefault();
		messagePresenter.hideMessages($messages);

		var userNameOrEmail = $el.find('form input[name=user]').val();
		if (userNameOrEmail.length === 0) {
			messagePresenter.showError($messages, 'Field cannot be blank.');
			return;
		}
		var url = operation === 'passwordReset' ?
			'/password-reset/' + userNameOrEmail :
			'/activation/' + userNameOrEmail;

		promise.wait(api.post(url))
			.then(function(response) {
				var message = operation === 'passwordReset' ?
					'Password reset request sent.' :
					'Activation e-mail resent.';
				message += ' Check your inbox.<br/>If e-mail doesn\'t show up, check your spam folder.';

				$el.find('#user-query-form').slideUp(function() {
					messagePresenter.showInfo($messages, message);
				});
			}).fail(function(response) {
				messagePresenter.showError($messages, response.json && response.json.error || response);
			});
	}

	function confirmToken(token) {
		messagePresenter.hideMessages($messages);

		var url = operation === 'passwordReset' ?
			'/finish-password-reset/' + token :
			'/finish-activation/' + token;

		promise.wait(api.post(url))
			.then(function(response) {
				var message = operation === 'passwordReset' ?
					'Your new password is <strong>' + response.json.newPassword + '</strong>.' :
					'E-mail activation successful.';

				$el.find('#user-query-form').slideUp(function() {
					messagePresenter.showInfo($messages, message);
				});
			}).fail(function(response) {
				messagePresenter.showError($messages, response.json && response.json.error || response);
			});
	}

	return {
		init: init,
		reinit: reinit,
		render: render,
	};

};

App.DI.register('userActivationPresenter', ['jQuery', 'promise', 'util', 'auth', 'api', 'router', 'topNavigationPresenter', 'messagePresenter'], App.Presenters.UserActivationPresenter);
