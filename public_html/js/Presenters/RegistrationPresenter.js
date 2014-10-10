var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.RegistrationPresenter = function(
	jQuery,
	util,
	promise,
	api,
	topNavigationPresenter,
	messagePresenter) {

	var $el = jQuery('#content');
	var templates = {};
	var $messages;

	function init(params, loaded) {
		topNavigationPresenter.select('register');
		topNavigationPresenter.changeTitle('Registration');
		promise.wait(util.promiseTemplate('registration-form'))
			.then(function(template) {
				templates.registration = template;
				render();
				loaded();
			}).fail(function() {
				console.log(arguments);
				loaded();
			});
	}

	function render() {
		$el.html(templates.registration());
		$el.find('form').submit(registrationFormSubmitted);
		$messages = $el.find('.messages');
		$messages.width($el.find('form').width());
	}

	function registrationFormSubmitted(e) {
		e.preventDefault();
		messagePresenter.hideMessages($messages);

		var formData = {
			userName: $el.find('[name=userName]').val(),
			password: $el.find('[name=password]').val(),
			passwordConfirmation: $el.find('[name=passwordConfirmation]').val(),
			email: $el.find('[name=email]').val(),
		};

		if (!validateRegistrationFormData(formData)) {
			return;
		}

		promise.wait(api.post('/users', formData))
			.then(function(response) {
				registrationSuccess(response);
			}).fail(function(response) {
				registrationFailure(response);
			});
	}

	function registrationSuccess(apiResponse) {
		$el.find('form').slideUp(function() {
			var message = 'Registration complete! ';
			if (!apiResponse.json.confirmed) {
				message += '<br/>Check your inbox for activation e-mail.<br/>If e-mail doesn\'t show up, check your spam folder.';
			} else {
				message += '<a href="#/login">Click here</a> to login.';
			}
			messagePresenter.showInfo($messages, message);
		});
	}

	function registrationFailure(apiResponse) {
		messagePresenter.showError($messages, apiResponse.json && apiResponse.json.error || apiResponse);
	}

	function validateRegistrationFormData(formData) {
		if (formData.userName.length === 0) {
			messagePresenter.showError($messages, 'User name cannot be empty.');
			return false;
		}

		if (formData.password.length === 0) {
			messagePresenter.showError($messages, 'Password cannot be empty.');
			return false;
		}

		if (formData.password !== formData.passwordConfirmation) {
			messagePresenter.showError($messages, 'Passwords must be the same.');
			return false;
		}

		return true;
	}

	return {
		init: init,
		render: render,
	};

};

App.DI.register('registrationPresenter', ['jQuery', 'util', 'promise', 'api', 'topNavigationPresenter', 'messagePresenter'], App.Presenters.RegistrationPresenter);
