var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.PostPresenter = function(
	_,
	jQuery,
	util,
	promise,
	api,
	topNavigationPresenter,
	messagePresenter) {

	var $el = jQuery('#content');
	var $messages = $el;
	var postTemplate;
	var postContentTemplate;
	var post;
	var postNameOrId;

	function init(args, loaded) {
		postNameOrId = args.postNameOrId;
		topNavigationPresenter.select('posts');

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
		}));
	}

	return {
		init: init,
		render: render
	};

};

App.DI.register('postPresenter', ['_', 'jQuery', 'util', 'promise', 'api', 'topNavigationPresenter', 'messagePresenter'], App.Presenters.PostPresenter);
