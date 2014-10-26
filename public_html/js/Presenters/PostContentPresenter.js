var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.PostContentPresenter = function(
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
			$target.find('.post-notes-target').width($target.find('.image-wrapper').outerWidth());
			$target.find('.post-notes-target').height($target.find('.image-wrapper').outerHeight());
		}
	}

	function loadPostNotes() {
		presenterManager.initPresenters([
			[postNotesPresenter, {post: post, notes: post.notes, $target: $target.find('.post-notes-target')}]],
			function() {});
	}

	function addNewPostNote() {
		postNotesPresenter.addNewPostNote();
	}

	return {
		init: init,
		render: render,
		addNewPostNote: addNewPostNote,
	};

};

App.DI.register('postContentPresenter', [
	'util',
	'promise',
	'presenterManager',
	'postNotesPresenter'],
	App.Presenters.PostContentPresenter);
