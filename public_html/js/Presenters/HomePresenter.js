var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.HomePresenter = function(
	jQuery,
	util,
	promise,
	api,
	auth,
	topNavigationPresenter,
	messagePresenter) {

	var $el = jQuery('#content');
	var templates = {};
	var globals;
	var post;

	function init(params, loaded) {
		topNavigationPresenter.select('home');
		topNavigationPresenter.changeTitle('Home');

		promise.wait(
				util.promiseTemplate('home'),
				util.promiseTemplate('post-content'),
				api.get('/globals'),
				api.get('/posts/featured'))
			.then(function(
					homeTemplate,
					postContentTemplate,
					globalsResponse,
					featuredPostResponse) {
				templates.home = homeTemplate;
				templates.postContent = postContentTemplate;

				globals = globalsResponse.json;
				post = featuredPostResponse.json.id ? featuredPostResponse.json : null;
				render();
				loaded();

			}).fail(function(response) {
				messagePresenter.showError($el, response.json && response.json.error || response);
				loaded();
			});
	}

	function render() {
		$el.html(templates.home({
			post: post,
			postContentTemplate: templates.postContent,
			globals: globals,
			title: topNavigationPresenter.getBaseTitle(),
			canViewUsers: auth.hasPrivilege(auth.privileges.viewUsers),
			canViewPosts: auth.hasPrivilege(auth.privileges.viewPosts),
			formatRelativeTime: util.formatRelativeTime,
			formatFileSize: util.formatFileSize,
		}));
	}

	return {
		init: init,
		render: render,
	};

};

App.DI.register('homePresenter', ['jQuery', 'util', 'promise', 'api', 'auth', 'topNavigationPresenter', 'messagePresenter'], App.Presenters.HomePresenter);
