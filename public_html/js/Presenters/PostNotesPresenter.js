var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.PostNotesPresenter = function(
	jQuery,
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
		var $postNotes = $target.find('.post-note');

		$postNotes.each(function(i) {
			var $postNote = jQuery(this);
			$postNote.data('postNote', notes[i]);
			$postNote.find('.text-wrapper').mouseup(postNoteClicked);
			makeDraggable($postNote);
			makeResizable($postNote);
		});
	}

	function postNoteClicked(e) {
		e.preventDefault();
		var $postNote = jQuery(e.currentTarget).parents('.post-note');
		if ($postNote.hasClass('resizing') || $postNote.hasClass('dragging')) {
			return;
		}
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
			var minX = $parent.offset().left;
			var minY = $parent.offset().top;
			var maxX = minX + $parent.outerWidth() - $element.outerWidth();
			var maxY = minY + $parent.outerHeight() - $element.outerHeight();

			var update = function(e) {
				var x = e.clientX + deltaX;
				var y = e.clientY + deltaY;
				x = Math.min(Math.max(x, minX), maxX);
				y = Math.min(Math.max(y, minY), maxY);
				$element.offset({left: x, top: y});
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
				var w = Math.max(20, e.clientX + deltaX);
				var h = Math.max(20, e.clientY + deltaY);
				$element.width(w);
				$element.height(h);
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
		addNewNote: addNewNoteAndRender,
	};

};

App.DI.register('postNotesPresenter', [
	'jQuery',
	'util',
	'promise'],
	App.Presenters.PostNotesPresenter);
