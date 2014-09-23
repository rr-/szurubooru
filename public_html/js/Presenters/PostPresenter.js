var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.PostPresenter = function(
	_,
	jQuery,
	util,
	promise,
	api,
	auth,
	router,
	topNavigationPresenter,
	messagePresenter) {

	var $el = jQuery('#content');
	var $messages = $el;
	var postTemplate;
	var postContentTemplate;
	var post;
	var postNameOrId;
	var privileges = {};

	function init(args, loaded) {
		postNameOrId = args.postNameOrId;
		topNavigationPresenter.select('posts');

		privileges.canDeletePosts = auth.hasPrivilege(auth.privileges.deletePosts);

		promise.waitAll(
				util.promiseTemplate('post'),
				util.promiseTemplate('post-content'),
				api.get('/posts/' + postNameOrId))
			.then(function(
					postTemplateHtml,
					postContentTemplateHtml,
					response) {
				$messages = $el.find('.messages');
				postTemplate = _.template(postTemplateHtml);
				postContentTemplate = _.template(postContentTemplateHtml);

				post = response.json;
				topNavigationPresenter.changeTitle('@' + post.id);
				render();
				loaded();

			}).fail(function(response) {
				$el.empty();
				messagePresenter.showError($messages, response.json && response.json.error || response);
			});
	}

	function render() {
		$el.html(postTemplate({
			post: post,
			formatRelativeTime: util.formatRelativeTime,
			formatFileSize: util.formatFileSize,
			postContentTemplate: postContentTemplate,
			privileges: privileges,
		}));

		$el.find('.delete').click(deleteButtonClicked);
	}

	function deleteButtonClicked(e) {
		e.preventDefault();
		if (window.confirm('Do you really want to delete this post?')) {
			deletePost();
		}
	}

	function deletePost() {
		api.delete('/posts/' + post.id).then(function(response) {
			router.navigate('#/posts');
		}).fail(function(response) {
			messagePresenter.showError($messages, response.json && response.json.error || response);
		});
	}

	return {
		init: init,
		render: render
	};

};

App.DI.register('postPresenter', ['_', 'jQuery', 'util', 'promise', 'api', 'auth', 'router', 'topNavigationPresenter', 'messagePresenter'], App.Presenters.PostPresenter);
