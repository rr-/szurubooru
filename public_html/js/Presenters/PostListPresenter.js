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

	var templates = {};
	var $el = jQuery('#content');
	var $searchInput;

	var params;

	function init(_params, loaded) {
		topNavigationPresenter.select('posts');
		topNavigationPresenter.changeTitle('Posts');
		params = _params;
		params.query = params.query || {};

		promise.wait(
				util.promiseTemplate('post-list'),
				util.promiseTemplate('post-list-item'))
			.then(function(listTemplate, listItemTemplate) {
				templates.list = listTemplate;
				templates.listItem = listItemTemplate;

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
						reinit(params, function() {});
					});
			});
	}

	function reinit(params, loaded) {
		pagerPresenter.reinit({query: params.query});
		loaded();
	}

	function deinit() {
		pagerPresenter.deinit();
	}

	function render() {
		$el.html(templates.list());
		$searchInput = $el.find('input[name=query]');
		App.Controls.AutoCompleteInput($searchInput);

		$searchInput.val(params.query.query);
		$searchInput.keydown(searchInputKeyPressed);
		$el.find('form').submit(searchFormSubmitted);

		keyboard.keyup('p', function() {
			$el.find('.posts li a').eq(0).focus();
		});

		keyboard.keyup('q', function() {
			$searchInput.eq(0).focus().select();
		});
	}

	function renderPosts(posts, clear) {
		var $target = $el.find('.posts');

		if (clear) {
			$target.empty();
		}

		_.each(posts, function(post) {
			var $post = jQuery('<li>' + templates.listItem({
				util: util,
				query: params.query,
				post: post,
			}) + '</li>');
			util.loadImagesNicely($post.find('img'));
			$target.append($post);
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
		params.query.query = $searchInput.val().trim();
		pagerPresenter.setQuery(params.query);
	}

	return {
		init: init,
		reinit: reinit,
		deinit: deinit,
		render: render,
	};

};

App.DI.register('postListPresenter', ['_', 'jQuery', 'util', 'promise', 'auth', 'keyboard', 'pagerPresenter', 'topNavigationPresenter'], App.Presenters.PostListPresenter);
