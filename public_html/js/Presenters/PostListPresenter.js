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
	topNavigationPresenter,
	messagePresenter) {

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

		promise.waitAll(
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
						failCallback: function(response) {
							$el.empty();
							messagePresenter.showError($el, response.json && response.json.error || response);
							loaded();
						}
					},
					function() {
						reinit(args, function() {});
					});
			});
	}

	function reinit(args, loaded) {
		loaded();

		searchArgs = util.parseComplexRouteArgs(args.searchArgs);
		pagerPresenter.reinit({
			page: searchArgs.page,
			searchParams: {
				query: searchArgs.query,
				order: searchArgs.order}});
	}

	function render() {
		$el.html(listTemplate());
		$searchInput = $el.find('input[name=query]');

		$searchInput.val(searchArgs.query);
		$searchInput.keydown(searchInputKeyPressed);

		keyboard.keyup('p', function() {
			$el.find('.posts li a').eq(0).focus();
		});

		keyboard.keyup('q', function() {
			$searchInput.eq(0).focus();
		});
	}

	function renderPosts(posts, clear) {
		var $target = $el.find('.posts');

		var all = '';
		_.each(posts, function(post) {
			all += itemTemplate({
				post: post,
			});
		});

		if (clear) {
			$target.html(all);
		} else {
			$target.append(all);
		}
	}

	function searchInputKeyPressed(e) {
		if (e.which !== KEY_RETURN) {
			return;
		}

		$searchInput.blur();
		pagerPresenter.setSearchParams({
			query: $searchInput.val(),
			order: searchArgs.order});
	}

	return {
		init: init,
		reinit: reinit,
		render: render,
	};

};

App.DI.register('postListPresenter', ['_', 'jQuery', 'util', 'promise', 'auth', 'keyboard', 'pagerPresenter', 'topNavigationPresenter', 'messagePresenter'], App.Presenters.PostListPresenter);
