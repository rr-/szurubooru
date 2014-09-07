var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.PasswordResetPresenter = function(
	jQuery,
	topNavigationPresenter,
	messagePresenter) {

	var $el = jQuery('#content');
	var $messages = $el;

	function init(args) {
		topNavigationPresenter.select('login');
		if (args.token) {
			alert('Got token');
		} else {
			render();
		}
	}

	function render() {
		$el.html('Password reset placeholder');
	};

	return {
		init: init,
		render: render,
	};

};

App.DI.register('passwordResetPresenter', App.Presenters.PasswordResetPresenter);
