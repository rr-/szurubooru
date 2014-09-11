var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.UserBrowsingSettingsPresenter = function(
	_,
	jQuery,
	util,
	promise,
	auth,
	browsingSettings,
	messagePresenter) {

	var target;
	var template;
	var user;
	var privileges = {};

	function init(args) {
		return promise.make(function(resolve, reject) {
			user = args.user;
			target = args.target;

			privileges.canChangeBrowsingSettings = auth.isLoggedIn(user.name) && user.name === auth.getCurrentUser().name;

			promise.wait(util.promiseTemplate('browsing-settings')).then(function(html) {
				template = _.template(html);
				render();
				resolve();
			});
		});
	}

	function render() {
		var $el = jQuery(target);
		$el.html(template({user: user, settings: browsingSettings.getSettings()}));
		$el.find('form').submit(browsingSettingsFormSubmitted);
	}

	function browsingSettingsFormSubmitted(e) {
		e.preventDefault();
		var $el = jQuery(target);
		var $messages = $el.find('.messages');
		messagePresenter.hideMessages($messages);
		var newSettings = {
			endlessScroll: $el.find('[name=endless-scroll]:visible').prop('checked'),
			hideDownvoted: $el.find('[name=hide-downvoted]:visible').prop('checked'),
			listPosts: {
				safe: $el.find('[name=listSafePosts]:visible').prop('checked'),
				sketchy: $el.find('[name=listSketchyPosts]:visible').prop('checked'),
				unsafe: $el.find('[name=listUnsafePosts]:visible').prop('checked'),
			},
		};
		browsingSettings.setSettings(newSettings).then(function() {
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

App.DI.register('userBrowsingSettingsPresenter', ['_', 'jQuery', 'util', 'promise', 'auth', 'browsingSettings', 'messagePresenter'], App.Presenters.UserBrowsingSettingsPresenter);
