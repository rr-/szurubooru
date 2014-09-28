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
	topNavigationPresenter,
	messagePresenter) {

	var $el = jQuery('#content');
	var $messages = $el;

	var postTemplate;
	var postEditTemplate;
	var postContentTemplate;
	var historyTemplate;

	var post;
	var postScore;
	var postFavorites;
	var postHistory;
	var postNameOrId;

	var privileges = {};
	var editPrivileges = {};

	var tagInput;
	var postContentFileDropper;
	var postThumbnailFileDropper;
	var postContent;
	var postThumbnail;

	function init(args, loaded) {
		topNavigationPresenter.select('posts');

		privileges.canDeletePosts = auth.hasPrivilege(auth.privileges.deletePosts);
		privileges.canFeaturePosts = auth.hasPrivilege(auth.privileges.featurePosts);
		privileges.canViewHistory = auth.hasPrivilege(auth.privileges.viewHistory);
		editPrivileges.canChangeSafety = auth.hasPrivilege(auth.privileges.changePostSafety);
		editPrivileges.canChangeSource = auth.hasPrivilege(auth.privileges.changePostSource);
		editPrivileges.canChangeTags = auth.hasPrivilege(auth.privileges.changePostTags);
		editPrivileges.canChangeContent = auth.hasPrivilege(auth.privileges.changePostContent);
		editPrivileges.canChangeThumbnail = auth.hasPrivilege(auth.privileges.changePostThumbnail);
		editPrivileges.canChangeRelations = auth.hasPrivilege(auth.privileges.changePostRelations);

		promise.waitAll(
				util.promiseTemplate('post'),
				util.promiseTemplate('post-edit'),
				util.promiseTemplate('post-content'),
				util.promiseTemplate('history'))
			.then(function(
					postTemplateHtml,
					postEditTemplateHtml,
					postContentTemplateHtml,
					historyTemplateHtml) {
				postTemplate = _.template(postTemplateHtml);
				postEditTemplate = _.template(postEditTemplateHtml);
				postContentTemplate = _.template(postContentTemplateHtml);
				historyTemplate = _.template(historyTemplateHtml);

				reinit(args, loaded);
			}).fail(function(response) {
				showGenericError(response);
				loaded();
			});
	}

	function reinit(args, loaded) {
		postNameOrId = args.postNameOrId;

		refreshPost()
			.then(function() {
				topNavigationPresenter.changeTitle('@' + post.id);
				render();
				loaded();
			});
	}

	function refreshPost() {
		return promise.make(function(resolve, reject) {
			promise.waitAll(
					api.get('/posts/' + postNameOrId),
					api.get('/posts/' + postNameOrId + '/favorites'),
					auth.isLoggedIn() ?
						api.get('/posts/' + postNameOrId + '/score') :
						null,
					privileges.canViewHistory ?
						api.get('/posts/' + postNameOrId + '/history') :
						null)
				.then(function(
						postResponse,
						postFavoritesResponse,
						postScoreResponse,
						postHistoryResponse) {
					post = postResponse.json;
					postScore = postScoreResponse && postScoreResponse.json && postScoreResponse.json.score;
					postFavorites = postFavoritesResponse && postFavoritesResponse.json && postFavoritesResponse.json.data;
					postHistory = postHistoryResponse && postHistoryResponse.json && postHistoryResponse.json.data;
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

		if (editPrivileges.canChangeTags) {
			tagInput = App.Controls.TagInput($el.find('form [name=tags]'), _, jQuery);
			tagInput.inputConfirmed = editPost;
		}

		postContentFileDropper = new App.Controls.FileDropper($el.find('form [name=content]'), _, jQuery);
		postContentFileDropper.onChange = postContentChanged;
		postContentFileDropper.setNames = true;
		postThumbnailFileDropper = new App.Controls.FileDropper($el.find('form [name=thumbnail]'), _, jQuery);
		postThumbnailFileDropper.onChange = postThumbnailChanged;
		postThumbnailFileDropper.setNames = true;

		if (_.any(editPrivileges)) {
			keyboard.keyup('e', function() {
				editButtonClicked(null);
			});
		}

		$el.find('.post-edit-wrapper form').submit(editFormSubmitted);
		attachSidebarEvents();
	}

	function renderSidebar() {
		$el.find('#sidebar').html(jQuery(renderPostTemplate()).find('#sidebar').html());
		attachSidebarEvents();
	}

	function renderPostTemplate() {
		return postTemplate({
			post: post,
			ownScore: postScore,
			postFavorites: postFavorites,
			postHistory: postHistory,

			formatRelativeTime: util.formatRelativeTime,
			formatFileSize: util.formatFileSize,

			postContentTemplate: postContentTemplate,
			postEditTemplate: postEditTemplate,
			historyTemplate: historyTemplate,

			hasFav: _.any(postFavorites, function(favUser) { return favUser.id === auth.getCurrentUser().id; }),
			isLoggedIn: auth.isLoggedIn(),
			privileges: privileges,
			editPrivileges: editPrivileges,
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
		api.delete('/posts/' + post.id)
			.then(function(response) {
				router.navigate('#/posts');
			}).fail(showGenericError);
	}

	function featureButtonClicked(e) {
		e.preventDefault();
		messagePresenter.hideMessages($messages);
		if (window.confirm('Do you want to feature this post on fron page?')) {
			featurePost();
		}
	}

	function featurePost() {
		api.post('/posts/' + post.id + '/feature')
			.then(function(response) {
				router.navigate('#/home');
			})
			.fail(showGenericError);
	}

	function editButtonClicked(e) {
		if (e) {
			e.preventDefault();
		}
		messagePresenter.hideMessages($messages);
		if ($el.find('.post-edit-wrapper').is(':visible')) {
			hideEditForm();
		} else {
			showEditForm();
		}
	}

	function editFormSubmitted(e) {
		e.preventDefault();
		editPost();
	}

	function showEditForm() {
		$el.find('.post-edit-wrapper').slideDown('fast');
		util.enableExitConfirmation();
		tagInput.focus();
	}

	function hideEditForm() {
		$el.find('.post-edit-wrapper').slideUp('fast');
		util.disableExitConfirmation();
	}

	function editPost() {
		var $form = $el.find('form');
		var formData = {};
		formData.seenEditTime = post.lastEditTime;

		if (editPrivileges.canChangeContent && postContent) {
			formData.content = postContent;
		}

		if (editPrivileges.canChangeThumbnail && postThumbnail) {
			formData.thumbnail = postThumbnail;
		}

		if (editPrivileges.canChangeSource) {
			formData.source = $form.find('[name=source]').val();
		}

		if (editPrivileges.canChangeSafety) {
			formData.safety = $form.find('[name=safety]:checked').val();
		}

		if (editPrivileges.canChangeTags) {
			formData.tags = tagInput.getTags().join(' ');
		}

		if (editPrivileges.canChangeRelations) {
			formData.relations = $form.find('[name=relations]').val();
		}

		if (post.tags.length === 0) {
			showEditError('No tags set.');
			return;
		}

		promise.wait(api.put('/posts/' + post.id, formData))
			.then(function(response) {
				post = response.json;
				hideEditForm();
				renderSidebar();
			}).fail(function(response) {
				showEditError(response);
			});
	}

	function postContentChanged(files) {
		postContentFileDropper.readAsDataURL(files[0], function(content) {
			postContent = content;
		});
	}

	function postThumbnailChanged(files) {
		postThumbnailFileDropper.readAsDataURL(files[0], function(content) {
			postThumbnail = content;
		});
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
		api.post('/posts/' + post.id + '/favorites')
			.then(function(response) {
				refreshPost().then(renderSidebar);
			})
			.fail(showGenericError);
	}

	function deleteFavorite() {
		api.delete('/posts/' + post.id + '/favorites')
			.then(function(response) {
				refreshPost().then(renderSidebar);
			})
			.fail(showGenericError);
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
		api.post('/posts/' + post.id + '/score', {score: scoreValue})
			.then(function() {
				refreshPost().then(renderSidebar);
			})
			.fail(showGenericError);
	}

	function showEditError(response) {
		window.alert(response.json && response.json.error || response);
	}

	function showGenericError(response) {
		messagePresenter.showError($messages, response.json && response.json.error || response);
	}

	return {
		init: init,
		reinit: reinit,
		render: render
	};

};

App.DI.register('postPresenter', ['_', 'jQuery', 'util', 'promise', 'api', 'auth', 'router', 'keyboard', 'topNavigationPresenter', 'messagePresenter'], App.Presenters.PostPresenter);
