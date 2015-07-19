var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.PostContentPresenter = function(
    jQuery,
    util,
    promise,
    keyboard,
    presenterManager,
    postNotesPresenter,
    browsingSettings) {

    var post;
    var templates = {};
    var $target;
    var $wrapper;

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

    function getFitters() {
        var originalWidth = $wrapper.attr('data-width');
        var originalHeight = $wrapper.attr('data-height');
        var ratio = originalWidth / originalHeight;
        var containerHeight = jQuery(window).height() - $wrapper.offset().top - 10;
        var containerWidth = $wrapper.parent().outerWidth() - 10;

        return {
            'fit-both': function(allowUpscale) {
                var width = containerWidth;
                var height = containerWidth / ratio;
                if (height > containerHeight) {
                    width = containerHeight * ratio;
                    height = containerHeight;
                }
                if (!allowUpscale) {
                    if (width > originalWidth) {
                        width = originalWidth;
                        height = originalWidth / ratio;
                    }
                    if (height > originalHeight) {
                        width = originalHeight * ratio;
                        height = originalHeight;
                    }
                }
                $wrapper.css({maxWidth: width + 'px', width: ''});
            },
            'fit-height': function(allowUpscale) {
                var width = containerHeight * ratio;
                if (width > originalWidth && !allowUpscale) {
                    width = originalWidth;
                }
                $wrapper.css({maxWidth: width + 'px', width: ''});
            },
            'fit-width': function(allowUpscale) {
                if (allowUpscale) {
                    $wrapper.css({maxWidth: containerWidth + 'px', width: ''});
                } else {
                    $wrapper.css({maxWidth: originalWidth + 'px', width: ''});
                }
            },
            'original': function(allowUpscale) {
                $wrapper.css({maxWidth: '', width: originalWidth + 'px'});
            }
        };
    }

    function getFitMode() {
        return $wrapper.data('fit-mode');
    }

    function changeFitMode(fitMode) {
        $wrapper.data('fit-mode', fitMode);
        getFitters()[fitMode.style](fitMode.upscale);
        updatePostNotesSize();
    }

    function cycleFitMode() {
        var oldMode = getFitMode();
        var fitterNames = Object.keys(getFitters());
        var newMode = {
            style: fitterNames[(fitterNames.indexOf(oldMode.style) + 1) % fitterNames.length],
            upscale: oldMode.upscale,
        };
        changeFitMode(newMode);
    }

    function render() {
        $target.html(templates.postContent({post: post}));
        $wrapper = $target.find('.object-wrapper');

        if (post.contentType === 'image') {
            loadPostNotes();
            updatePostNotesSize();
        }

        changeFitMode({
            style: browsingSettings.getSettings().fitMode,
            upscale: browsingSettings.getSettings().upscale,
        });
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
        getFitMode: getFitMode,
        changeFitMode: changeFitMode,
        cycleFitMode: cycleFitMode,
    };
};

App.DI.register('postContentPresenter', [
    'jQuery',
    'util',
    'promise',
    'keyboard',
    'presenterManager',
    'postNotesPresenter',
    'browsingSettings'],
    App.Presenters.PostContentPresenter);
