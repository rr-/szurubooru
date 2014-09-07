var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.UserPresenter = function(
	jQuery,
	util,
	promise,
	api,
	auth,
	topNavigationPresenter,
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

	function init(args) {
		userName = args.userName;
		topNavigationPresenter.select(auth.isLoggedIn(userName) ? 'my-account' : 'users');

		promise.waitAll(
			util.promiseTemplate('user'),
			api.get('/users/' + userName))
		.then(function(
				userHtml,
				response) {
			$messages = $el.find('.messages');
			template = _.template(userHtml);

			user = response.json;
			var extendedContext = _.extend(args, {user: user});

			promise.waitAll(
				userBrowsingSettingsPresenter.init(_.extend(extendedContext, {target: '#browsing-settings-target'})),
				userAccountSettingsPresenter.init(_.extend(extendedContext, {target: '#account-settings-target'})),
				userAccountRemovalPresenter.init(_.extend(extendedContext, {target: '#account-removal-target'})))
			.then(function() {
				initTabs(args);
			})

		}).fail(function(response) {
			$el.empty();
			messagePresenter.showError($messages, response.json && response.json.error || response);
		});
	}

	function initTabs(args) {
		activeTab = args.tab || 'basic-info';
		render();
	}

	function render() {
		$el.html(template({
			user: user,
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
		var tab = $link.attr('data-tab');
		var $tabs = $link.closest('.tab-wrapper').find('.tab');
		$links.removeClass('active');
		$link.addClass('active');
		$tabs.removeClass('active');
		$tabs.filter('[data-tab=' + tab + ']').addClass('active');
	}

	return {
		init: init,
		reinit: initTabs,
		render: render
	};

};

App.DI.register('userPresenter', App.Presenters.UserPresenter);
