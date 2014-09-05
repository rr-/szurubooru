var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.UserPresenter = function(
	jQuery,
	util,
	promise,
	api,
	auth,
	topNavigationPresenter,
	messagePresenter) {

	var $el = jQuery('#content');
	var $messages = $el;
	var template;
	var accountSettingsTemplate;
	var accountRemovalTemplate;
	var browsingSettingsTemplate;
	var user;
	var userName;

	function init(args) {
		userName = args.userName;
		topNavigationPresenter.select(auth.isLoggedIn() && auth.getCurrentUser().name == userName ? 'my-account' : 'users');

		promise.waitAll(
			util.promiseTemplate('user'),
			util.promiseTemplate('account-settings'),
			util.promiseTemplate('account-removal'),
			util.promiseTemplate('browsing-settings'),
			api.get('/users/' + userName))
		.then(function(
				userHtml,
				accountSettingsHtml,
				accountRemovalHtml,
				browsingSettingsHtml,
				response) {
			template = _.template(userHtml);
			accountSettingsTemplate = _.template(accountSettingsHtml);
			accountRemovalTemplate = _.template(accountRemovalHtml);
			browsingSettingsTemplate = _.template(browsingSettingsHtml);

			user = response.json;
			render();
		}).fail(function(response) {
			$el.empty();
			messagePresenter.showError($messages, response.json && response.json.error || response);
		});
	}

	function render() {
		var context = {
			user: user,
			canDeleteAccount: auth.hasPrivilege('deleteAccounts') ||
				(auth.hasPrivilege('deleteOwnAccount') && auth.getCurrentUser().name == userName),
		};
		$el.html(template(context));
		$el.find('.browsing-settings').html(browsingSettingsTemplate(context));
		$el.find('.account-settings').html(accountSettingsTemplate(context));
		$el.find('.account-removal').html(accountRemovalTemplate(context));
		$el.find('.account-removal form').submit(accountRemovalFormSubmitted);
		$messages = $el.find('.messages');
	};

	function accountRemovalFormSubmitted(e) {
		e.preventDefault();
		$messages = $el.find('.account-removal .messages');
		messagePresenter.hideMessages($messages);
		if (!$el.find('.account-removal input[name=confirmation]:visible').prop('checked')) {
			messagePresenter.showError($messages, 'Must confirm to proceed.');
			return;
		}
		api.delete('/users/' + user.name)
			.then(function() {
				auth.logout();
				var $messageDiv = messagePresenter.showInfo($messages, 'Account deleted. <a href="">Back to main page</a>');
				$messageDiv.find('a').click(mainPageLinkClicked);
			}).fail(function(response) {
				messagePresenter.showError($messages, response.json && response.json.error || response);
			});
	}

	return {
		init: init,
		render: render
	};

};

App.DI.register('userPresenter', App.Presenters.UserPresenter);
