var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.PostContentPresenter = function(
	jQuery,
	util,
	promise,
	presenterManager,
	postNotesPresenter) {

	var post;
	var templates = {};
	var $target;

	function init(params, loaded) {
		$target = params.$target;
		post = params.post;

		promise.wait(util.promiseTemplate('post-content'))
			.then(function(postContentTemplate) {
				templates.postContent = postContentTemplate;
				render();
				loaded();
			}).fail(function() {
				console.log(arguments);
				loaded();
			});
	}

	function render() {
		$target.html(templates.postContent({post: post}));

		if (post.contentType === 'image') {
			loadPostNotes();
			updatePostNotesSize();
		}

		jQuery(window).resize(updatePostNotesSize);
	}

	function loadPostNotes() {
		presenterManager.initPresenters([
			[postNotesPresenter, {post: post, notes: post.notes, $target: $target.find('.post-notes-target')}]],
			function() {});
	}

	function updatePostNotesSize() {
		$target.find('.post-notes-target').width($target.find('.object-wrapper').outerWidth());
		$target.find('.post-notes-target').height($target.find('.object-wrapper').outerHeight());
	}

	function addNewPostNote() {
		postNotesPresenter.addNewPostNote();
	}

	return {
		init: init,
		render: render,
		addNewPostNote: addNewPostNote,
		updatePostNotesSize: updatePostNotesSize,
	};

};

App.DI.register('postContentPresenter', [
	'jQuery',
	'util',
	'promise',
	'presenterManager',
	'postNotesPresenter'],
	App.Presenters.PostContentPresenter);
