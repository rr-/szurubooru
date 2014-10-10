var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.PostListPresenter = function(
	_,
	jQuery,
	util,
	promise,
	auth,
	api,
	keyboard,
	pagerPresenter,
	topNavigationPresenter) {

	var KEY_RETURN = 13;

	var templates = {};
	var $el = jQuery('#content');
	var $searchInput;
	var privileges = {};

	var params;

	function init(_params, loaded) {
		topNavigationPresenter.select('posts');
		topNavigationPresenter.changeTitle('Posts');
		params = _params;
		params.query = params.query || {};

		privileges.canMassTag = auth.hasPrivilege(auth.privileges.massTag);

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

		jQuery(window).on('resize', windowResized);
	}

	function reinit(params, loaded) {
		pagerPresenter.reinit({query: params.query});
		loaded();
		softRender();
	}

	function deinit() {
		pagerPresenter.deinit();
		jQuery(window).off('resize', windowResized);
	}

	function render() {
		$el.html(templates.list({massTag: params.query.massTag, privileges: privileges}));
		$searchInput = $el.find('input[name=query]');
		App.Controls.AutoCompleteInput($searchInput);

		$searchInput.val(params.query.query);
		$searchInput.keydown(searchInputKeyPressed);
		$el.find('form').submit(searchFormSubmitted);
		$el.find('[name=mass-tag]').click(massTagButtonClicked);

		keyboard.keyup('p', function() {
			$el.find('.posts li a').eq(0).focus();
		});

		keyboard.keyup('q', function() {
			$searchInput.eq(0).focus().select();
		});

		windowResized();
	}

	function softRender() {
		$searchInput.val(params.query.query);

		var $massTagInfo = $el.find('.mass-tag-info');
		if (params.query.massTag) {
			$massTagInfo.show();
			$massTagInfo.find('span').text(params.query.massTag);
		} else {
			$massTagInfo.hide();
		}
		_.map($el.find('.posts .post-small'), function(postNode) { softRenderPost(jQuery(postNode).parents('li')); });
	}

	function renderPosts(posts, clear) {
		var $target = $el.find('.posts');

		if (clear) {
			$target.empty();
		}

		_.each(posts, function(post) {
			var $post = renderPost(post);
			softRenderPost($post);
			$target.append($post);
		});
		windowResized();
	}

	function renderPost(post) {
		var $post = jQuery('<li>' + templates.listItem({
			util: util,
			query: params.query,
			post: post,
		}) + '</li>');
		$post.data('post', post);
		util.loadImagesNicely($post.find('img'));
		return $post;
	}

	function softRenderPost($post) {
		var classes = [];
		if (params.query.massTag) {
			var post = $post.data('post');
			if (_.contains(_.map(post.tags, function(tag) { return tag.name.toLowerCase(); }), params.query.massTag.toLowerCase())) {
				classes.push('tagged');
			} else {
				classes.push('untagged');
			}
		}
		$post.toggleClass('tagged', _.contains(classes, 'tagged'));
		$post.toggleClass('untagged', _.contains(classes, 'untagged'));
		$post.find('.action').toggle(_.any(classes));
		$post.find('.action button').text(_.contains(classes, 'tagged') ? 'Tagged' : 'Untagged').unbind('click').click(postTagButtonClicked);
	}

	function windowResized() {
		var $list = $el.find('ul.posts');
		var $posts = $list.find('.post-small');
		var $firstPost = $posts.eq(0);
		var $lastPost = $firstPost;
		for (var i = 1; i < $posts.length; i ++) {
			$lastPost = $posts.eq(i-1);
			if ($posts.eq(i).offset().left < $lastPost.offset().left) {
				break;
			}
		}
		if ($firstPost.length === 0) {
			return;
		}
		$el.find('.search').css('margin-left', $firstPost.offset().left - $list.offset().left);
		$el.find('.search').css('margin-right', $list.width() - ($lastPost.offset().left - $list.offset().left + $lastPost.width()));
	}

	function postTagButtonClicked(e) {
		e.preventDefault();
		var $post = jQuery(e.target).parents('li');
		var post = $post.data('post');
		var tags = _.pluck(post.tags, 'name');
		if (_.contains(_.map(tags, function(tag) { return tag.toLowerCase(); }), params.query.massTag.toLowerCase())) {
			tags = _.filter(tags, function(tag) { return tag.toLowerCase() !== params.query.massTag.toLowerCase(); });
		} else {
			tags.push(params.query.massTag);
		}
		var formData = {};
		formData.seenEditTime = post.lastEditTime;
		formData.tags = tags.join(' ');
		promise.wait(api.put('/posts/' + post.id, formData))
			.then(function(response) {
				post = response.json;
				$post.data('post', post);
				softRenderPost($post);
			}).fail(function(response) {
				console.log(response);
			});
	}

	function searchInputKeyPressed(e) {
		if (e.which !== KEY_RETURN) {
			return;
		}
		updateSearch();
	}

	function massTagButtonClicked(e) {
		e.preventDefault();
		params.query.massTag = window.prompt('Enter tag to tag with:');
		pagerPresenter.setQuery(params.query);
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

App.DI.register('postListPresenter', ['_', 'jQuery', 'util', 'promise', 'auth', 'api', 'keyboard', 'pagerPresenter', 'topNavigationPresenter'], App.Presenters.PostListPresenter);
