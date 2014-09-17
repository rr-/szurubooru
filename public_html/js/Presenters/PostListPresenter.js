var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.PostListPresenter = function(
	_,
	jQuery,
	util,
	promise,
	auth,
	router,
	pagedCollectionPresenter,
	topNavigationPresenter,
	messagePresenter) {

	var $el = jQuery('#content');
	var listTemplate;
	var itemTemplate;

	function init(args, loaded) {
		topNavigationPresenter.select('posts');
		topNavigationPresenter.changeTitle('Posts');

		promise.waitAll(
				util.promiseTemplate('post-list'),
				util.promiseTemplate('post-list-item'))
			.then(function(listHtml, itemHtml) {
				listTemplate = _.template(listHtml);
				itemTemplate = _.template(itemHtml);

				render();
				reinit(args, loaded);
			});
	}

	function reinit(args, loaded) {
		var searchArgs = util.parseComplexRouteArgs(args.searchArgs);
		searchArgs.order = searchArgs.order;

		updateActiveOrder(searchArgs.order);
		initPaginator(searchArgs, loaded);
	}

	function initPaginator(searchArgs, onLoad) {
		pagedCollectionPresenter.init({
			page: searchArgs.page,
			searchParams: {order: searchArgs.order},
			baseUri: '#/posts',
			backendUri: '/posts',
			updateCallback: function(data, clear) {
				renderPosts(data.entities, clear);
				return $el.find('.pagination-content');
			},
			failCallback: function(response) {
				$el.empty();
				messagePresenter.showError($el, response.json && response.json.error || response);
			}}, onLoad);
	}

	function render() {
		$el.html(listTemplate());
	}

	function updateActiveOrder(activeOrder) {
		$el.find('.order li a').removeClass('active');
		$el.find('.order [data-order="' + activeOrder + '"]').addClass('active');
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

	return {
		init: init,
		reinit: reinit,
		render: render,
	};

};

App.DI.register('postListPresenter', ['_', 'jQuery', 'util', 'promise', 'auth', 'router', 'pagedCollectionPresenter', 'topNavigationPresenter', 'messagePresenter'], App.Presenters.PostListPresenter);
