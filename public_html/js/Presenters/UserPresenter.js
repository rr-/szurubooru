var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.UserPresenter = function(
	_,
	jQuery,
	util,
	promise,
	api,
	auth,
	topNavigationPresenter,
	presenterManager,
	userBrowsingSettingsPresenter,
	userAccountSettingsPresenter,
	userAccountRemovalPresenter,
	messagePresenter) {

	var $el = jQuery('#content');
	var $messages = $el;
	var template;
	var user;
	var userName;
	var activeTab;

	function init(args, loaded) {
		userName = args.userName;
		topNavigationPresenter.select(auth.isLoggedIn(userName) ? 'my-account' : 'users');
		topNavigationPresenter.changeTitle(userName);

		promise.wait(
				util.promiseTemplate('user'),
				api.get('/users/' + userName))
			.then(function(
					userHtml,
					response) {
				$messages = $el.find('.messages');
				template = _.template(userHtml);

				user = response.json;
				var extendedContext = _.extend(args, {user: user});

				presenterManager.initPresenters([
					[userBrowsingSettingsPresenter, _.extend({}, extendedContext, {target: '#browsing-settings-target'})],
					[userAccountSettingsPresenter, _.extend({}, extendedContext, {target: '#account-settings-target'})],
					[userAccountRemovalPresenter, _.extend({}, extendedContext, {target: '#account-removal-target'})]],
					function() {
						reinit(args, loaded);
					});

			}).fail(function(response) {
				$el.empty();
				messagePresenter.showError($messages, response.json && response.json.error || response);
				loaded();
			});
	}

	function reinit(args, loaded) {
		initTabs(args);
		loaded();
	}

	function initTabs(args) {
		activeTab = args.tab || 'basic-info';
		render();
	}

	function render() {
		$el.html(template({
			user: user,
			formatRelativeTime: util.formatRelativeTime,
			canChangeBrowsingSettings: userBrowsingSettingsPresenter.getPrivileges().canChangeBrowsingSettings,
			canChangeAccountSettings: _.any(userAccountSettingsPresenter.getPrivileges()),
			canDeleteAccount: userAccountRemovalPresenter.getPrivileges().canDeleteAccount}));
		userBrowsingSettingsPresenter.render();
		userAccountSettingsPresenter.render();
		userAccountRemovalPresenter.render();
		changeTab(activeTab);
	}

	function changeTab(targetTab) {
		var $link = $el.find('a[data-tab=' + targetTab + ']');
		var $links = $link.closest('ul').find('a[data-tab]');
		var $tabs = $el.find('.tab-wrapper').find('.tab');
		$links.removeClass('active');
		$link.addClass('active');
		$tabs.removeClass('active');
		$tabs.filter('[data-tab=' + targetTab + ']').addClass('active');
	}

	return {
		init: init,
		reinit: reinit,
		render: render
	};

};

App.DI.register('userPresenter', ['_', 'jQuery', 'util', 'promise', 'api', 'auth', 'topNavigationPresenter', 'presenterManager', 'userBrowsingSettingsPresenter', 'userAccountSettingsPresenter', 'userAccountRemovalPresenter', 'messagePresenter'], App.Presenters.UserPresenter);
