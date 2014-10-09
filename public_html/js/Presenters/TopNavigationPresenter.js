var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.TopNavigationPresenter = function(
	jQuery,
	util,
	promise,
	auth) {

	var selectedElement = null;
	var $el = jQuery('#top-navigation');
	var templates = {};
	var baseTitle = document.title;

	function init(params, loaded) {
		promise.wait(util.promiseTemplate('top-navigation'))
			.then(function(template) {
				templates.topNavigation = template;
				render();
				loaded();
				auth.startObservingLoginChanges('top-navigation', loginStateChanged);
			});
	}

	function select(newSelectedElement) {
		selectedElement = newSelectedElement;
		$el.find('li a').removeClass('active');
		$el.find('li.' + selectedElement).find('a').addClass('active');
	}

	function loginStateChanged() {
		render();
	}

	function render() {
		$el.html(templates.topNavigation({
			loggedIn: auth.isLoggedIn(),
			user: auth.getCurrentUser(),
			canListUsers: auth.hasPrivilege(auth.privileges.listUsers),
			canListPosts: auth.hasPrivilege(auth.privileges.listPosts),
			canListTags: auth.hasPrivilege(auth.privileges.listTags),
			canUploadPosts: auth.hasPrivilege(auth.privileges.uploadPosts),
		}));
		$el.find('li.' + selectedElement).find('a').addClass('active');
	}

	function focus() {
		var $tmp = jQuery('<a href="#"> </a>');
		$el.prepend($tmp);
		$tmp.focus();
		$tmp.remove();
	}

	function getBaseTitle() {
		return baseTitle;
	}

	function changeTitle(subTitle) {
		var newTitle = baseTitle;
		if (subTitle) {
			newTitle += ' - ' + subTitle;
		}
		document.title = newTitle;
	}

	return {
		init: init,
		render: render,
		select: select,
		focus: focus,
		getBaseTitle: getBaseTitle,
		changeTitle: changeTitle,
	};

};

App.DI.registerSingleton('topNavigationPresenter', ['jQuery', 'util', 'promise', 'auth'], App.Presenters.TopNavigationPresenter);
