var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.PostNotesPresenter = function(
    jQuery,
    util,
    promise,
    api,
    auth,
    draggable,
    resizable) {

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
            util: util}));

        $form = $target.find('.post-note-edit');
        var $postNotes = $target.find('.post-note');

        $postNotes.each(function(i) {
            var postNote = notes[i];
            var $postNote = jQuery(this);
            $postNote.data('postNote', postNote);
            $postNote.find('.text-wrapper').click(postNoteClicked);
            postNote.$element = $postNote;
            draggable.makeDraggable($postNote, draggable.relativeDragStrategy, true);
            resizable.makeResizable($postNote, true);
            $postNote.mouseenter(function() { postNoteMouseEnter(postNote); });
            $postNote.mouseleave(function() { postNoteMouseLeave(postNote); });
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
        } else if (sender === 'preview') {
            previewPostNote(postNote);
        }
    }

    function removePostNote(postNote) {
        if (postNote.id) {
            if (window.confirm('Are you sure you want to delete this note?')) {
                promise.wait(api.delete('/notes/' + postNote.id))
                    .then(function() {
                        hideForm();
                        notes = jQuery.grep(notes, function(otherNote) {
                            return otherNote.id !== postNote.id;
                        });
                        render();
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
                    postNote.id = response.json.note.id;
                    postNote.$element.data('postNote', postNote);
                    render();
                }).fail(function(response) {
                    window.alert(response.json && response.json.error || response);
                });
        }
    }

    function previewPostNote(postNote) {
        var previewText = $form.find('textarea').val();
        postNote.$element.find('.text').html(util.formatMarkdown(previewText));
        showPostNoteText(postNote);
    }

    function showPostNoteText(postNote) {
        var $textWrapper = postNote.$element.find('.text-wrapper');
        $textWrapper.show();
        if ($textWrapper.offset().left + $textWrapper.width() > jQuery(window).outerWidth()) {
            $textWrapper.offset({left: jQuery(window).outerWidth() - $textWrapper.width()});
        }
    }

    function hidePostNoteText(postNote) {
        postNote.$element.find('.text-wrapper').css('display', '');
    }

    function postNoteMouseEnter(postNote) {
        showPostNoteText(postNote);
    }

    function postNoteMouseLeave(postNote) {
        hidePostNoteText(postNote);
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
        hideForm();
        var postNote = $postNote.data('postNote');
        $form.data('postNote', postNote);
        $form.find('textarea').val(postNote.text);
        $form.show();
        draggable.makeDraggable($form, draggable.absoluteDragStrategy, false);
    }

    function hideForm() {
        var previousPostNote = $form.data('post-note');
        if (previousPostNote) {
            hidePostNoteText(previousPostNote);
        }
        $form.hide();
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
    'auth',
    'draggable',
    'resizable'],
    App.Presenters.PostNotesPresenter);
