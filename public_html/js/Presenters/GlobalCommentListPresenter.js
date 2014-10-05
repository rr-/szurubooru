var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.GlobalCommentListPresenter = function(
	_,
	jQuery,
	util,
	promise,
	pagerPresenter,
	topNavigationPresenter) {

	var $el;
	var templates = {};

	function init(args, loaded) {
		$el = jQuery('#content');
		topNavigationPresenter.select('comments');

		promise.wait(
				util.promiseTemplate('global-comment-list'),
				util.promiseTemplate('global-comment-list-item'),
				util.promiseTemplate('post-list-item'))
			.then(function(listTemplate, listItemTemplate, postTemplate)
				{
					templates.list = listTemplate;
					templates.listItem = listItemTemplate;
					templates.post = postTemplate;

					render();
					loaded();

					pagerPresenter.init({
							baseUri: '#/comments',
							backendUri: '/comments',
							$target: $el.find('.pagination-target'),
							updateCallback: function(data, clear) {
								renderPosts(data.entities, clear);
							},
						},
						function() {
							onArgsChanged(args);
						});
				})
			.fail(function() { console.log(new Error(arguments)); });
	}


	function reinit(args, loaded) {
		loaded();
		onArgsChanged(args);
	}

	function onArgsChanged(args) {
		var searchArgs = util.parseComplexRouteArgs(args.searchArgs);
		pagerPresenter.reinit({
			page: searchArgs.page,
			searchParams: {
				query: searchArgs.query,
				order: searchArgs.order}});
	}

	function deinit() {
		pagerPresenter.deinit();
	}

	function render() {
		$el.html(templates.list());
	}

	function renderPosts(data, clear) {
		var $target = $el.find('.posts');

		if (clear) {
			$target.empty();
		}

		_.each(data, function(data) {
			var post = data.post;
			var comments = data.comments;

			var $post = jQuery('<li>' + templates.listItem({
				post: post,
				postTemplate: templates.post,
			}) + '</li>');

			util.loadImagesNicely($post.find('img'));
			var presenter = App.DI.get('postCommentListPresenter');

			presenter.init({
				post: post,
				comments: comments,
				$target: $post.find('.post-comments-target'),
			}, function() {
				presenter.render();
			});

			$target.append($post);
		});
	}

	return {
		init: init,
		reinit: reinit,
		deinit: deinit,
		render: render,
	};
};

App.DI.register('globalCommentListPresenter', ['_', 'jQuery', 'util', 'promise', 'pagerPresenter', 'topNavigationPresenter'], App.Presenters.GlobalCommentListPresenter);
