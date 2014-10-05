var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.PostListPresenter = function(
	_,
	jQuery,
	util,
	promise,
	auth,
	keyboard,
	pagerPresenter,
	topNavigationPresenter) {

	var KEY_RETURN = 13;

	var $el = jQuery('#content');
	var $searchInput;
	var listTemplate;
	var itemTemplate;

	var searchArgs;

	function init(args, loaded) {
		topNavigationPresenter.select('posts');
		topNavigationPresenter.changeTitle('Posts');
		searchArgs = util.parseComplexRouteArgs(args.searchArgs);
		searchArgs.page = parseInt(searchArgs.page) || 1;

		promise.wait(
				util.promiseTemplate('post-list'),
				util.promiseTemplate('post-list-item'))
			.then(function(listHtml, itemHtml) {
				listTemplate = _.template(listHtml);
				itemTemplate = _.template(itemHtml);

				render();
				loaded();

				pagerPresenter.init({
						baseUri: '#/posts',
						backendUri: '/posts',
						$target: $el.find('.pagination-target'),
						updateCallback: function(data, clear) {
							renderPosts(data.entities, clear);
						},
					},
					function() {
						onArgsChanged(args);
					});
			});
	}

	function reinit(args, loaded) {
		loaded();
		onArgsChanged(args);
	}

	function onArgsChanged(args) {
		searchArgs = util.parseComplexRouteArgs(args.searchArgs);
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
		$el.html(listTemplate());
		$searchInput = $el.find('input[name=query]');

		$searchInput.val(searchArgs.query);
		$searchInput.keydown(searchInputKeyPressed);
		$el.find('form').submit(searchFormSubmitted);

		keyboard.keyup('p', function() {
			$el.find('.posts li a').eq(0).focus();
		});

		keyboard.keyup('q', function() {
			$searchInput.eq(0).focus();
		});
	}

	function renderPosts(posts, clear) {
		var $target = $el.find('.posts');

		if (clear) {
			$target.empty();
		}

		_.each(posts, function(post) {
			$target.append(jQuery('<li>' + itemTemplate({
				searchArgs: searchArgs,
				post: post,
			}) + '</li>'));
		});
	}

	function searchInputKeyPressed(e) {
		if (e.which !== KEY_RETURN) {
			return;
		}
		updateSearch();
	}

	function searchFormSubmitted(e) {
		e.preventDefault();
		updateSearch();
	}

	function updateSearch() {
		$searchInput.blur();
		pagerPresenter.setSearchParams({
			query: $searchInput.val(),
			order: searchArgs.order});
	}

	return {
		init: init,
		reinit: reinit,
		deinit: deinit,
		render: render,
	};

};

App.DI.register('postListPresenter', ['_', 'jQuery', 'util', 'promise', 'auth', 'keyboard', 'pagerPresenter', 'topNavigationPresenter'], App.Presenters.PostListPresenter);
