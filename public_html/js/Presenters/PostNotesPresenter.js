var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.PostNotesPresenter = function(
	jQuery,
	util,
	promise,
	api,
	auth) {

	var post;
	var notes;
	var templates = {};
	var $target;
	var $form;
	var privileges = {};

	function init(params, loaded) {
		$target = params.$target;
		post = params.post;
		notes = params.notes || [];

		privileges.canDeletePostNotes = auth.hasPrivilege(auth.privileges.deletePostNotes);
		privileges.canEditPostNotes = auth.hasPrivilege(auth.privileges.editPostNotes);

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

	function addNewPostNote() {
		notes.push({left: 10.0, top: 10.0, width: 10.0, height: 10.0, text: '…'});
	}

	function addNewPostNoteAndRender() {
		addNewPostNote();
		render();
	}

	function render() {
		$target.html(templates.postNotes({
			privileges: privileges,
			post: post,
			notes: notes,
			formatMarkdown: util.formatMarkdown}));

		$form = $target.find('.post-note-edit');
		var $postNotes = $target.find('.post-note');

		$postNotes.each(function(i) {
			var postNote = notes[i];
			var $postNote = jQuery(this);
			$postNote.data('postNote', postNote);
			$postNote.find('.text-wrapper').click(postNoteClicked);
			postNote.$element = $postNote;
			makeDraggable($postNote);
			makeResizable($postNote);
		});

		$form.find('button').click(formSubmitted);
	}

	function formSubmitted(e) {
		e.preventDefault();
		var $button = jQuery(e.target);
		var sender = $button.val();

		var postNote = $form.data('postNote');
		postNote.left = (postNote.$element.offset().left - $target.offset().left) * 100.0 / $target.outerWidth();
		postNote.top = (postNote.$element.offset().top - $target.offset().top) * 100.0 / $target.outerHeight();
		postNote.width = postNote.$element.width() * 100.0 / $target.outerWidth();
		postNote.height = postNote.$element.height() * 100.0 / $target.outerHeight();
		postNote.text = $form.find('textarea').val();

		if (sender === 'cancel') {
			hideForm();
		} else if (sender === 'remove') {
			removePostNote(postNote);
		} else if (sender === 'save') {
			savePostNote(postNote);
		}
	}

	function removePostNote(postNote) {
		if (postNote.id) {
			if (window.confirm('Are you sure you want to delete this note?')) {
				promise.wait(api.delete('/notes/' + postNote.id))
					.then(function() {
						hideForm();
						postNote.$element.remove();
					}).fail(function(response) {
						window.alert(response.json && response.json.error || response);
					});
			}
		} else {
			postNote.$element.remove();
			hideForm();
		}
	}

	function savePostNote(postNote) {
		if (window.confirm('Are you sure you want to save this note?')) {
			var formData = {
				left: postNote.left,
				top: postNote.top,
				width: postNote.width,
				height: postNote.height,
				text: postNote.text,
			};

			var p = postNote.id ?
				api.put('/notes/' + postNote.id, formData) :
				api.post('/notes/' + post.id, formData);

			promise.wait(p)
				.then(function(response) {
					hideForm();
					postNote.id = response.json.id;
					postNote.$element.data('postNote', postNote);
					render();
				}).fail(function(response) {
					window.alert(response.json && response.json.error || response);
				});
		}
	}

	function postNoteClicked(e) {
		e.preventDefault();
		var $postNote = jQuery(e.currentTarget).parents('.post-note');
		if ($postNote.hasClass('resizing') || $postNote.hasClass('dragging')) {
			return;
		}
		showFormForPostNote($postNote);
	}

	function showFormForPostNote($postNote) {
		var postNote = $postNote.data('postNote');
		$form.data('postNote', postNote);
		$form.find('textarea').val(postNote.text);
		$form.show();
	}

	function hideForm() {
		$form.hide();
	}

	function makeDraggable($element) {
		var $dragger = jQuery('<div class="dragger"></div>');
		$element.prepend($dragger);

		$dragger.mousedown(function(e) {
			e.preventDefault();
			$element.addClass('dragging');

			var $parent = $element.parent();
			var deltaX = $element.offset().left - e.clientX;
			var deltaY = $element.offset().top - e.clientY;

			var update = function(e) {
				var x = e.clientX + deltaX - $parent.offset().left;
				var y = e.clientY + deltaY - $parent.offset().top;
				x = Math.min(Math.max(x, 0), $parent.outerWidth() - $element.outerWidth());
				y = Math.min(Math.max(y, 0), $parent.outerHeight() - $element.outerHeight());
				x *= 100.0 / $parent.outerWidth();
				y *= 100.0 / $parent.outerHeight();
				$element.css({
					left: x + '%',
					top: y + '%'});
			};

			jQuery(window).bind('mousemove.elemmove', function(e) {
				update(e);
			}).bind('mouseup.elemmove', function(e) {
				e.preventDefault();
				update(e);
				$element.removeClass('dragging');
				jQuery(window).unbind('mousemove.elemmove');
				jQuery(window).unbind('mouseup.elemmove');
			});
		});
	}

	function makeResizable($element) {
		var $resizer = jQuery('<div class="resizer"></div>');
		$element.append($resizer);

		$resizer.mousedown(function(e) {
			e.preventDefault();
			e.stopPropagation();
			$element.addClass('resizing');

			var $parent = $element.parent();
			var deltaX = $element.width() - e.clientX;
			var deltaY = $element.height() - e.clientY;

			var update = function(e) {
				var w = e.clientX + deltaX;
				var h = e.clientY + deltaY;
				w = Math.min(Math.max(w, 20), $parent.outerWidth() + $parent.offset().left - $element.offset().left);
				h = Math.min(Math.max(h, 20), $parent.outerHeight() + $parent.offset().top - $element.offset().top);
				w *= 100.0 / $parent.outerWidth();
				h *= 100.0 / $parent.outerHeight();
				$element.css({
					width: w + '%',
					height: h + '%'});
			};

			jQuery(window).bind('mousemove.elemsize', function(e) {
				update(e);
			}).bind('mouseup.elemsize', function(e) {
				e.preventDefault();
				update(e);
				$element.removeClass('resizing');
				jQuery(window).unbind('mousemove.elemsize');
				jQuery(window).unbind('mouseup.elemsize');
			});
		});
	}

	return {
		init: init,
		render: render,
		addNewPostNote: addNewPostNoteAndRender,
	};

};

App.DI.register('postNotesPresenter', [
	'jQuery',
	'util',
	'promise',
	'api',
	'auth'],
	App.Presenters.PostNotesPresenter);
