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
	var browsingSettingsTemplate;
	var user;
	var userName;

	function init(args) {
		userName = args.userName;
		topNavigationPresenter.select(auth.isLoggedIn() && auth.getCurrentUser().name == userName ? 'my-account' : 'users');

		promise.waitAll(
			util.promiseTemplate('user'),
			util.promiseTemplate('account-settings'),
			util.promiseTemplate('browsing-settings'),
			api.get('/users/' + userName))
		.then(function(userHtml, accountSettingsHtml, browsingSettingsHtml, response) {
			template = _.template(userHtml);
			accountSettingsTemplate = _.template(accountSettingsHtml);
			browsingSettingsTemplate = _.template(browsingSettingsHtml);

			user = response.json;
			render();
		}).fail(function(response) {
			$el.empty();
			messagePresenter.showError($messages, response.json && response.json.error || response);
		});
	}

	function render() {
		$el.html(template({user: user}));
		$el.find('.browsing-settings').html(browsingSettingsTemplate({user: user}));
		$el.find('.account-settings').html(accountSettingsTemplate({user: user}));
		$messages = $el.find('.messages');
	};

	return {
		init: init,
		render: render
	};

};

App.DI.register('userPresenter', App.Presenters.UserPresenter);
