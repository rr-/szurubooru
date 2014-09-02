var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.RegistrationPresenter = function(
	jQuery,
	util,
	topNavigationPresenter,
	messagePresenter,
	api) {

	topNavigationPresenter.select('register');

	var $el = jQuery('#content');
	var template;

	util.loadTemplate('registration-form').then(function(html) {
		template = _.template(html);
		render();
	});

	var eventHandlers = {

		registrationFormSubmit: function(e) {
			e.preventDefault();
			messagePresenter.hideMessages($messages);

			registrationData = {
				userName: $el.find('[name=user]').val(),
				password: $el.find('[name=password1]').val(),
				passwordConfirmation: $el.find('[name=password2]').val(),
				email: $el.find('[name=email]').val(),
			};

			if (!validateRegistrationData(registrationData))
				return;

			api.post('/users', registrationData)
				.then(function(response) {
					eventHandlers.registrationSuccess(response);
				}).catch(function(response) {
					eventHandlers.registrationFailure(response);
				});
		},

		registrationSuccess: function(apiResponse) {
			//todo: tell user if it turned out that he needs to confirm his e-mail
			$el.find('form').slideUp(function() {
				var message = 'Registration complete! ';
				message += '<a href="#/login">Click here</a> to login.';
				messagePresenter.showInfo($messages, message);
			});
		},

		registrationFailure: function(apiResponse) {
			messagePresenter.showError($messages, apiResponse.json && apiResponse.json.error || apiResponse);
		},
	};

	function render() {
		$el.html(template());
		$el.find('form').submit(eventHandlers.registrationFormSubmit);
		$messages = $el.find('.messages');
		$messages.width($el.find('form').width());
	};

	function validateRegistrationData(registrationData) {
		if (registrationData.userName.length == 0) {
			messagePresenter.showError($messages, 'User name cannot be empty.');
			return false;
		}

		if (registrationData.password.length == 0) {
			messagePresenter.showError($messages, 'Password cannot be empty.');
			return false;
		}

		if (registrationData.password != registrationData.passwordConfirmation) {
			messagePresenter.showError($messages, 'Passwords must be the same.');
			return false;
		}

		return true;
	};

	return {
		render: render,
	};

};

App.DI.register('registrationPresenter', App.Presenters.RegistrationPresenter);
