var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.PostContentPresenter = function(
    jQuery,
    util,
    promise,
    keyboard,
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

    var fitters = {
        'fit-height': function($wrapper) {
            var originalWidth = $wrapper.attr('data-width');
            var originalHeight = $wrapper.attr('data-height');
            var ratio = originalWidth / originalHeight;
            var height = jQuery(window).height() - $wrapper.offset().top;
            var width = (height - 10) * ratio;
            $wrapper.css({maxWidth: width + 'px'});
        },
        'fit-width': function($wrapper) {
            var originalWidth = $wrapper.attr('data-width');
            $wrapper.css({maxWidth: originalWidth + 'px', width: 'auto'});
        },
        'original': function($wrapper) {
            var originalWidth = $wrapper.attr('data-width');
            $wrapper.css({maxWidth: null, width: originalWidth + 'px'});
        }
    };
    var fitterNames = Object.keys(fitters);

    function changeFitMode(mode) {
        var $wrapper = $target.find('.object-wrapper');

        $wrapper.data('current-fit', mode);
        fitters[$wrapper.data('current-fit')]($wrapper);
    }

    function cycleFitMode() {
        var $wrapper = $target.find('.object-wrapper');
        var mode = $wrapper.data('current-fit');
        var newMode = fitterNames[(fitterNames.indexOf(mode) + 1) % fitterNames.length];
        $wrapper.data('current-fit', newMode);
        fitters[$wrapper.data('current-fit')]($wrapper);
        updatePostNotesSize();
    }

    function render() {
        $target.html(templates.postContent({post: post}));

        if (post.contentType === 'image') {
            loadPostNotes();
            updatePostNotesSize();
        }

        changeFitMode('fit-width');
        keyboard.keyup('f', cycleFitMode);

        jQuery(window).resize(updatePostNotesSize);
    }

    function loadPostNotes() {
        presenterManager.initPresenters([
            [postNotesPresenter, {post: post, notes: post.notes, $target: $target.find('.post-notes-target')}]],
            function() {});
    }

    function updatePostNotesSize() {
        var $postNotes = $target.find('.post-notes-target');
        var $wrapper = $target.find('.object-wrapper');
        $postNotes.css({
            width: $wrapper.outerWidth() + 'px',
            height: $wrapper.outerHeight() + 'px',
            left: ($wrapper.offset().left - $wrapper.parent().offset().left) + 'px',
            top: ($wrapper.offset().top - $wrapper.parent().offset().top) + 'px',
        });
    }

    function addNewPostNote() {
        postNotesPresenter.addNewPostNote();
    }

    return {
        init: init,
        render: render,
        addNewPostNote: addNewPostNote,
        updatePostNotesSize: updatePostNotesSize,
        cycleFitMode: cycleFitMode,
    };
};

App.DI.register('postContentPresenter', [
    'jQuery',
    'util',
    'promise',
    'keyboard',
    'presenterManager',
    'postNotesPresenter'],
    App.Presenters.PostContentPresenter);
