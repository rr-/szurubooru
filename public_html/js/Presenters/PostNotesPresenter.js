var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.PostNotesPresenter = function(
	util,
	promise) {

	var post;
	var notes;
	var templates = {};
	var $target;

	function init(params, loaded) {
		$target = params.$target;
		post = params.post;
		notes = params.notes || [];

		promise.wait(util.promiseTemplate('post-notes'))
			.then(function(postNotesTemplate) {
				templates.postNotes = postNotesTemplate;
				render();
				loaded();
			}).fail(function() {
				console.log(arguments);
				loaded();
			});
	}

	function addNewNote() {
		notes.push({left: 50, top: 50, width: 50, height: 50, text: '&hellip;'});
	}

	function addNewNoteAndRender() {
		addNewNote();
		render();
	}

	function render() {
		$target.html(templates.postNotes({post: post, notes: notes}));
	}

	return {
		init: init,
		render: render,
		addNewNote: addNewNoteAndRender,
	};

};

App.DI.register('postNotesPresenter', [
	'util',
	'promise'],
	App.Presenters.PostNotesPresenter);

