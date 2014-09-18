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
	var template;
	var post;
	var postNameOrId;

	function init(args, loaded) {
		postNameOrId = args.postNameOrId;
		topNavigationPresenter.select('posts');

		promise.waitAll(
				util.promiseTemplate('post'),
				api.get('/posts/' + postNameOrId))
			.then(function(
					templatehtml,
					response) {
				$messages = $el.find('.messages');
				template = _.template(templatehtml);

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
		$el.html(template({
			post: post,
			formatRelativeTime: util.formatRelativeTime,
		}));
	}

	return {
		init: init,
		render: render
	};

};

App.DI.register('postPresenter', ['_', 'jQuery', 'util', 'promise', 'api', 'topNavigationPresenter', 'messagePresenter'], App.Presenters.PostPresenter);
