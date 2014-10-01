var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.PostListPresenter = function(
	_,
	jQuery,
	util,
	promise,
	auth,
	router,
	keyboard,
	pagerPresenter,
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

		var searchArgs = util.parseComplexRouteArgs(args.searchArgs);
		pagerPresenter.reinit({page: searchArgs.page, searchParams: {query: searchArgs.query, order: searchArgs.order}});
	}

	function render() {
		$el.html(listTemplate());

		keyboard.keyup('p', function() {
			$el.find('.posts li a').eq(0).focus();
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

	return {
		init: init,
		reinit: reinit,
		render: render,
	};

};

App.DI.register('postListPresenter', ['_', 'jQuery', 'util', 'promise', 'auth', 'router', 'keyboard', 'pagerPresenter', 'topNavigationPresenter', 'messagePresenter'], App.Presenters.PostListPresenter);
