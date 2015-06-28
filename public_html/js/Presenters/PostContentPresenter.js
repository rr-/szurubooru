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
        var $postNotes = $target.find('.post-notes-target');
        var $objectWrapper = $target.find('.object-wrapper');
        $postNotes.css({
            width: $objectWrapper.outerWidth() + 'px',
            height: $objectWrapper.outerHeight() + 'px',
            left: ($objectWrapper.offset().left - $objectWrapper.parent().offset().left) + 'px',
            top: ($objectWrapper.offset().top - $objectWrapper.parent().offset().top) + 'px',
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
    };

};

App.DI.register('postContentPresenter', [
    'jQuery',
    'util',
    'promise',
    'presenterManager',
    'postNotesPresenter'],
    App.Presenters.PostContentPresenter);
