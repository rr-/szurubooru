var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.UserAccountRemovalPresenter = function(
	_,
	jQuery,
	util,
	promise,
	api,
	auth,
	router,
	messagePresenter) {

	var target;
	var template;
	var user;
	var privileges = {};

	function init(args) {
		return promise.make(function(resolve, reject) {
			user = args.user;
			target = args.target;

			privileges.canDeleteAccount =
				auth.hasPrivilege(auth.privileges.deleteAllAccounts) ||
				(auth.hasPrivilege(auth.privileges.deleteOwnAccount) && auth.isLoggedIn(user.name));

			promise.wait(util.promiseTemplate('account-removal')).then(function(html) {
				template = _.template(html);
				render();
				resolve();
			});
		});
	}

	function render() {
		var $el = jQuery(target);
		$el.html(template({
			user: user,
			canDeleteAccount: privileges.canDeleteAccount}));

		$el.find('form').submit(accountRemovalFormSubmitted);
	}

	function getPrivileges() {
		return privileges;
	}

	function accountRemovalFormSubmitted(e) {
		e.preventDefault();
		var $el = jQuery(target);
		var $messages = $el.find('.messages');
		messagePresenter.hideMessages($messages);
		if (!$el.find('input[name=confirmation]:visible').prop('checked')) {
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

	function mainPageLinkClicked(e) {
		e.preventDefault();
		router.navigateToMainPage();
	}

	return {
		init: init,
		render: render,
		getPrivileges: getPrivileges
	};

};

App.DI.register('userAccountRemovalPresenter', App.Presenters.UserAccountRemovalPresenter);
