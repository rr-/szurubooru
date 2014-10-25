var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.PostPresenter = function(
	_,
	jQuery,
	util,
	promise,
	api,
	auth,
	router,
	keyboard,
	presenterManager,
	postsAroundCalculator,
	postEditPresenter,
	postContentPresenter,
	postCommentListPresenter,
	topNavigationPresenter,
	messagePresenter) {

	var $el = jQuery('#content');
	var $messages = $el;

	var templates = {};
	var params;

	var postNameOrId;
	var post;

	var privileges = {};

	function init(params, loaded) {
		topNavigationPresenter.select('posts');
		postsAroundCalculator.resetCache();

		privileges.canDeletePosts = auth.hasPrivilege(auth.privileges.deletePosts);
		privileges.canFeaturePosts = auth.hasPrivilege(auth.privileges.featurePosts);
		privileges.canViewHistory = auth.hasPrivilege(auth.privileges.viewHistory);

		promise.wait(
				util.promiseTemplate('post'),
				util.promiseTemplate('history'))
			.then(function(
					postTemplate,
					historyTemplate) {
				templates.post = postTemplate;
				templates.history = historyTemplate;

				reinit(params, loaded);
			}).fail(function(response) {
				showGenericError(response);
				loaded();
			});
	}

	function reinit(_params, loaded) {
		params = _params;
		params.query = params.query || {};
		params.query.page = parseInt(params.query.page) || 1;

		postNameOrId = params.postNameOrId;

		promise.wait(refreshPost())
			.then(function() {
				topNavigationPresenter.changeTitle('@' + post.id);
				render();
				loaded();

				presenterManager.initPresenters([
					[postContentPresenter, {post: post, $target: $el.find('#post-content-target')}],
					[postEditPresenter, {post: post, $target: $el.find('#post-edit-target'), updateCallback: postEdited}],
					[postCommentListPresenter, {post: post, $target: $el.find('#post-comments-target')}]],
					function() {});

			}).fail(function() {
				console.log(arguments);
				loaded();
			});

	}

	function attachLinksToPostsAround() {
		promise.wait(postsAroundCalculator.getLinksToPostsAround(params.query, post.id))
			.then(function(nextPostUrl, prevPostUrl) {
				var $prevPost = $el.find('#post-current-search .right a');
				var $nextPost = $el.find('#post-current-search .left a');

				if (nextPostUrl) {
					$nextPost.addClass('enabled');
					$nextPost.attr('href', nextPostUrl);
					keyboard.keyup('a', function() {
						router.navigate(nextPostUrl);
					});
				} else {
					$nextPost.removeClass('enabled');
					$nextPost.removeAttr('href');
					keyboard.unbind('a');
				}

				if (prevPostUrl) {
					$prevPost.addClass('enabled');
					$prevPost.attr('href', prevPostUrl);
					keyboard.keyup('d', function() {
						router.navigate(prevPostUrl);
					});
				} else {
					$prevPost.removeClass('enabled');
					$prevPost.removeAttr('href');
					keyboard.unbind('d');
				}
			});
	}

	function refreshPost() {
		return promise.make(function(resolve, reject) {
			promise.wait(api.get('/posts/' + postNameOrId))
				.then(function(postResponse) {
					post = postResponse.json;
					resolve();
				}).fail(function(response) {
					showGenericError(response);
					reject();
				});
		});
	}

	function render() {
		$el.html(renderPostTemplate());
		$messages = $el.find('.messages');

		keyboard.keyup('e', function() {
			editButtonClicked(null);
		});

		attachSidebarEvents();

		attachLinksToPostsAround();
	}

	function postEdited(newPost) {
		post = newPost;
		hideEditForm();
		softRender();
	}

	function softRender() {
		renderSidebar();
		$el.find('video').prop('loop', post.flags.loop);
	}

	function renderSidebar() {
		$el.find('#sidebar').html(jQuery(renderPostTemplate()).find('#sidebar').html());
		attachSidebarEvents();
	}

	function renderPostTemplate() {
		return templates.post({
			query: params.query,
			post: post,
			ownScore: post.ownScore,
			postFavorites: post.favorites,
			postHistory: post.history,

			formatRelativeTime: util.formatRelativeTime,
			formatFileSize: util.formatFileSize,

			historyTemplate: templates.history,

			hasFav: _.any(post.favorites, function(favUser) { return favUser.id === auth.getCurrentUser().id; }),
			isLoggedIn: auth.isLoggedIn(),
			privileges: privileges,
			editPrivileges: postEditPresenter.getPrivileges(),
		});
	}

	function attachSidebarEvents() {
		$el.find('#sidebar .delete').click(deleteButtonClicked);
		$el.find('#sidebar .feature').click(featureButtonClicked);
		$el.find('#sidebar .edit').click(editButtonClicked);
		$el.find('#sidebar .history').click(historyButtonClicked);
		$el.find('#sidebar .add-favorite').click(addFavoriteButtonClicked);
		$el.find('#sidebar .delete-favorite').click(deleteFavoriteButtonClicked);
		$el.find('#sidebar .score-up').click(scoreUpButtonClicked);
		$el.find('#sidebar .score-down').click(scoreDownButtonClicked);
	}

	function deleteButtonClicked(e) {
		e.preventDefault();
		messagePresenter.hideMessages($messages);
		if (window.confirm('Do you really want to delete this post?')) {
			deletePost();
		}
	}

	function deletePost() {
		promise.wait(api.delete('/posts/' + post.id))
			.then(function(response) {
				router.navigate('#/posts');
			}).fail(showGenericError);
	}

	function featureButtonClicked(e) {
		e.preventDefault();
		messagePresenter.hideMessages($messages);
		if (window.confirm('Do you want to feature this post on the front page?')) {
			featurePost();
		}
	}

	function featurePost() {
		promise.wait(api.post('/posts/' + post.id + '/feature'))
			.then(function(response) {
				router.navigate('#/home');
			}).fail(showGenericError);
	}

	function editButtonClicked(e) {
		if (e) {
			e.preventDefault();
		}
		messagePresenter.hideMessages($messages);
		if ($el.find('#post-edit-target').is(':visible')) {
			hideEditForm();
		} else {
			showEditForm();
		}
	}

	function showEditForm() {
		$el.find('#post-edit-target').slideDown('fast');
		util.enableExitConfirmation();
		postEditPresenter.focus();
	}

	function hideEditForm() {
		$el.find('#post-edit-target').slideUp('fast');
		util.disableExitConfirmation();
	}

	function historyButtonClicked(e) {
		e.preventDefault();
		if ($el.find('.post-history-wrapper').is(':visible')) {
			hideHistory();
		} else {
			showHistory();
		}
	}

	function hideHistory() {
		$el.find('.post-history-wrapper').slideUp('slow');
	}

	function showHistory() {
		$el.find('.post-history-wrapper').slideDown('slow');
	}

	function addFavoriteButtonClicked(e) {
		e.preventDefault();
		addFavorite();
	}

	function deleteFavoriteButtonClicked(e) {
		e.preventDefault();
		deleteFavorite();
	}

	function addFavorite() {
		promise.wait(api.post('/posts/' + post.id + '/favorites'))
			.then(function(response) {
				promise.wait(refreshPost()).then(softRender);
			}).fail(showGenericError);
	}

	function deleteFavorite() {
		promise.wait(api.delete('/posts/' + post.id + '/favorites'))
			.then(function(response) {
				promise.wait(refreshPost()).then(softRender);
			}).fail(showGenericError);
	}

	function scoreUpButtonClicked(e) {
		e.preventDefault();
		var $target = jQuery(this);
		score($target.hasClass('active') ? 0 : 1);
	}

	function scoreDownButtonClicked(e) {
		e.preventDefault();
		var $target = jQuery(this);
		score($target.hasClass('active') ? 0 : -1);
	}

	function score(scoreValue) {
		promise.wait(api.post('/posts/' + post.id + '/score', {score: scoreValue}))
			.then(function() {
				promise.wait(refreshPost()).then(softRender);
			}).fail(showGenericError);
	}

	function showGenericError(response) {
		if ($messages === $el) {
			$el.empty();
		}
		messagePresenter.showError($messages, response.json && response.json.error || response);
	}

	return {
		init: init,
		reinit: reinit,
		render: render
	};

};

App.DI.register('postPresenter', [
	'_',
	'jQuery',
	'util',
	'promise',
	'api',
	'auth',
	'router',
	'keyboard',
	'presenterManager',
	'postsAroundCalculator',
	'postEditPresenter',
	'postContentPresenter',
	'postCommentListPresenter',
	'topNavigationPresenter',
	'messagePresenter'],
	App.Presenters.PostPresenter);
