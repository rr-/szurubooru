var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.UserBrowsingSettingsPresenter = function(
	jQuery,
	util,
	promise,
	auth,
	browsingSettings,
	messagePresenter) {

	var target;
	var templates = {};
	var user;
	var privileges = {};

	function init(args, loaded) {
		user = args.user;
		target = args.target;

		privileges.canChangeBrowsingSettings = auth.isLoggedIn(user.name) && user.name === auth.getCurrentUser().name;

		promise.wait(util.promiseTemplate('browsing-settings'))
			.then(function(template) {
				templates.browsingSettings = template;
				render();
				loaded();
			});
	}

	function render() {
		var $el = jQuery(target);
		$el.html(templates.browsingSettings({user: user, settings: browsingSettings.getSettings()}));
		$el.find('form').submit(browsingSettingsFormSubmitted);
	}

	function browsingSettingsFormSubmitted(e) {
		e.preventDefault();
		var $el = jQuery(target);
		var $messages = $el.find('.messages');
		messagePresenter.hideMessages($messages);

		var newSettings = {
			endlessScroll: $el.find('[name=endlessScroll]').is(':checked'),
			hideDownvoted: $el.find('[name=hideDownvoted]').is(':checked'),
			listPosts: {
				safe: $el.find('[name=listSafePosts]').is(':checked'),
				sketchy: $el.find('[name=listSketchyPosts]').is(':checked'),
				unsafe: $el.find('[name=listUnsafePosts]').is(':checked'),
			},
		};

		promise.wait(browsingSettings.setSettings(newSettings))
			.then(function() {
				messagePresenter.showInfo($messages, 'Browsing settings updated!');
			});
	}

	function getPrivileges() {
		return privileges;
	}

	return {
		init: init,
		render: render,
		getPrivileges: getPrivileges,
	};

};

App.DI.register('userBrowsingSettingsPresenter', ['jQuery', 'util', 'promise', 'auth', 'browsingSettings', 'messagePresenter'], App.Presenters.UserBrowsingSettingsPresenter);
