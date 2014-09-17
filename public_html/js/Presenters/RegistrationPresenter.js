var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.RegistrationPresenter = function(
	_,
	jQuery,
	util,
	promise,
	api,
	topNavigationPresenter,
	messagePresenter) {

	var $el = jQuery('#content');
	var template;
	var $messages;

	function init(args, loaded) {
		topNavigationPresenter.select('register');
		topNavigationPresenter.changeTitle('Registration');
		promise.wait(util.promiseTemplate('registration-form'))
			.then(function(html) {
				template = _.template(html);
				render();
				loaded();
			});
	}

	function render() {
		$el.html(template());
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

		api.post('/users', formData)
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

App.DI.register('registrationPresenter', ['_', 'jQuery', 'util', 'promise', 'api', 'topNavigationPresenter', 'messagePresenter'], App.Presenters.RegistrationPresenter);
