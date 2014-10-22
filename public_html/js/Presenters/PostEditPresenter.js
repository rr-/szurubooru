var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.PostEditPresenter = function(
	util,
	promise,
	api,
	auth) {

	var $target;
	var post;
	var updateCallback;
	var privileges = {};
	var templates = {};

	var tagInput;
	var postContentFileDropper;
	var postThumbnailFileDropper;
	var postContent;
	var postThumbnail;

	privileges.canChangeSafety = auth.hasPrivilege(auth.privileges.changePostSafety);
	privileges.canChangeSource = auth.hasPrivilege(auth.privileges.changePostSource);
	privileges.canChangeTags = auth.hasPrivilege(auth.privileges.changePostTags);
	privileges.canChangeContent = auth.hasPrivilege(auth.privileges.changePostContent);
	privileges.canChangeThumbnail = auth.hasPrivilege(auth.privileges.changePostThumbnail);
	privileges.canChangeRelations = auth.hasPrivilege(auth.privileges.changePostRelations);
	privileges.canChangeFlags = auth.hasPrivilege(auth.privileges.changePostFlags);

	function init(params, loaded) {
		post = params.post;

		updateCallback = params.updateCallback;
		$target = params.$target;

		promise.wait(util.promiseTemplate('post-edit'))
			.then(function(postEditTemplate) {
				templates.postEdit = postEditTemplate;
				render();
				loaded();
			}).fail(function() {
				console.log(arguments);
				loaded();
			});
	}

	function render() {
		$target.html(templates.postEdit({post: post, privileges: privileges}));

		postContentFileDropper = new App.Controls.FileDropper($target.find('form [name=content]'));
		postContentFileDropper.onChange = postContentChanged;
		postContentFileDropper.setNames = true;
		postThumbnailFileDropper = new App.Controls.FileDropper($target.find('form [name=thumbnail]'));
		postThumbnailFileDropper.onChange = postThumbnailChanged;
		postThumbnailFileDropper.setNames = true;

		if (privileges.canChangeTags) {
			tagInput = new App.Controls.TagInput($target.find('form [name=tags]'));
			tagInput.inputConfirmed = editPost;
		}

		$target.find('.post-edit-wrapper form').submit(editFormSubmitted);
	}

	function focus() {
		if (tagInput) {
			tagInput.focus();
		}
	}

	function editFormSubmitted(e) {
		e.preventDefault();
		editPost();
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

	function getPrivileges() {
		return privileges;
	}

	function editPost() {
		var $form = $target.find('form');
		var formData = {};
		formData.seenEditTime = post.lastEditTime;
		formData.flags = {};

		if (privileges.canChangeContent && postContent) {
			formData.content = postContent;
		}

		if (privileges.canChangeThumbnail && postThumbnail) {
			formData.thumbnail = postThumbnail;
		}

		if (privileges.canChangeSource) {
			formData.source = $form.find('[name=source]').val();
		}

		if (privileges.canChangeSafety) {
			formData.safety = $form.find('[name=safety]:checked').val();
		}

		if (privileges.canChangeTags) {
			formData.tags = tagInput.getTags().join(' ');
		}

		if (privileges.canChangeRelations) {
			formData.relations = $form.find('[name=relations]').val();
		}

		if (privileges.canChangeFlags) {
			if (post.contentType === 'video') {
				formData.flags.loop = $form.find('[name=loop]').is(':checked') ? 1 : 0;
			}
		}

		if (post.tags.length === 0) {
			showEditError('No tags set.');
			return;
		}

		promise.wait(api.put('/posts/' + post.id, formData))
			.then(function(response) {
				if (typeof(updateCallback) !== 'undefined') {
					updateCallback(post = response.json);
				}
			}).fail(function(response) {
				showEditError(response);
			});
	}

	function showEditError(response) {
		window.alert(response.json && response.json.error || response);
	}

	return {
		init: init,
		render: render,
		getPrivileges: getPrivileges,
		focus: focus,
	};

};

App.DI.register('postEditPresenter', ['util', 'promise', 'api', 'auth'], App.Presenters.PostEditPresenter);
