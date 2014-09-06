var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.UserBrowsingSettingsPresenter = function(
	jQuery,
	util,
	promise,
	auth) {

	var target;
	var template;
	var user;
	var privileges = {};

	function init(args) {
		return promise.make(function(resolve, reject) {
			user = args.user;
			target = args.target;

			privileges.canChangeBrowsingSettings = auth.isLoggedIn(user.name) && user.name == auth.getCurrentUser().name;

			promise.wait(util.promiseTemplate('browsing-settings')).then(function(html) {
				template = _.template(html);
				render();
				resolve();
			});
		});
	}

	function render() {
		$el = jQuery(target);
		$el.html(template({user: user}));
	}

	function getPrivileges() {
		return privileges;
	}

	return {
		init: init,
		render: render,
		getPrivileges: getPrivileges,
	};
}

App.DI.register('userBrowsingSettingsPresenter', App.Presenters.UserBrowsingSettingsPresenter);
