var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.TagPresenter = function(
	_,
	jQuery,
	util,
	promise,
	api,
	keyboard,
	topNavigationPresenter) {

	var $el = jQuery('#content');
	var templates = {};

	var tagName;

	function init(args, loaded) {
		topNavigationPresenter.select('tags');
		topNavigationPresenter.changeTitle('Tags');

		promise.wait(
				util.promiseTemplate('tag'),
				util.promiseTemplate('post-list-item'))
			.then(function(tagTemplate, postListItemTemplate) {
				templates.tag = tagTemplate;
				templates.postListItem = postListItemTemplate;

				reinit(args, loaded);
			});
	}

	function reinit(args, loaded) {
		tagName = args.tagName;

		render();
		loaded();

		promise.wait(api.get('posts', {query: tagName}))
			.then(function(response) {
				var posts = response.json.data;
				posts = posts.slice(0, 8);
				renderPosts(posts);
			}).fail(function(response) {
				console.log(new Error(response));
			});
	}

	function render() {
		$el.html(templates.tag({tagName: tagName}));
		$el.find('.post-list').hide();
	}

	function renderPosts(posts) {
		var $target = $el.find('.post-list ul');
		_.each(posts, function(post) {
			var $post = jQuery('<li>' + templates.postListItem({
				post: post,
				searchArgs: {query: tagName},
			}) + '</li>');
			$target.append($post);
		});
		if (posts.length > 0) {
			$el.find('.post-list').fadeIn();
			keyboard.keyup('p', function() {
				$el.find('.post-list a').eq(0).focus();
			});
		}
	}

	return {
		init: init,
		reinit: reinit,
	};
};

App.DI.register('tagPresenter', ['_', 'jQuery', 'util', 'promise', 'api', 'keyboard', 'topNavigationPresenter'], App.Presenters.TagPresenter);
