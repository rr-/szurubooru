var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.RegistrationPresenter = function(
	jQuery,
	topNavigationPresenter,
	messagePresenter,
	api) {

	topNavigationPresenter.select('register');

	var $el = jQuery('#content');
	var template = _.template(jQuery('#registration-form-template').html());

	var eventHandlers = {

		registrationFormSubmit: function(e) {
			e.preventDefault();
			messagePresenter.hideMessages($messages);

			var userName = $el.find('[name=user]').val();
			var password = $el.find('[name=password1]').val();
			var passwordConfirmation = $el.find('[name=password2]').val();
			var email = $el.find('[name=email]').val();

			if (userName.length == 0) {
				messagePresenter.showError($messages, 'User name cannot be empty.');
				return;
			}

			if (password.length == 0) {
				messagePresenter.showError($messages, 'Password cannot be empty.');
				return;
			}

			if (password != passwordConfirmation) {
				messagePresenter.showError($messages, 'Passwords must be the same.');
				return;
			}

			api.post('/users', {userName: userName, password: password, email: email})
				.then(function(response) {
					//todo: show message about registration success
					//if it turned out that user needs to confirm his e-mail, notify about it
					//also, show link to login
				}).catch(function(response) {
					messagePresenter.showError($messages, response.json && response.json.error || response);
				});
		}

	};

	render();

	function render() {
		$el.html(template());
		$el.find('form').submit(eventHandlers.registrationFormSubmit);
		$messages = $el.find('.messages');
		$messages.width($el.find('form').width());
	};

	return {
		render: render,
	};

};

App.DI.register('registrationPresenter', App.Presenters.RegistrationPresenter);
