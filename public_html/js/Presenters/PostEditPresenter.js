var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.PostEditPresenter = function(
    jQuery,
    util,
    promise,
    api,
    auth,
    tagList) {

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
        var $template = jQuery(templates.postEdit({post: post, privileges: privileges}));

        var $advanced = $template.find('.advanced');
        var $advancedTrigger = $template.find('.advanced-trigger');
        $advanced.hide();
        if (!$advanced.length) {
            $advancedTrigger.hide();
        } else {
            $advancedTrigger.find('a').click(function(e) {
                advancedTriggerClicked(e, $advanced, $advancedTrigger);
            });
        }

        $target.html($template);

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

        $target.find('form').submit(editFormSubmitted);
    }

    function advancedTriggerClicked(e, $advanced, $advancedTrigger) {
        $advancedTrigger.hide();
        $advanced.show();
        e.preventDefault();
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
        postContent = files[0];
    }

    function postThumbnailChanged(files) {
        postThumbnail = files[0];
    }

    function getPrivileges() {
        return privileges;
    }

    function editPost() {
        var $form = $target.find('form');
        var formData = new FormData();
        formData.append('seenEditTime', post.lastEditTime);

        if (privileges.canChangeContent && postContent) {
            formData.append('content', postContent);
        }

        if (privileges.canChangeThumbnail && postThumbnail) {
            formData.append('thumbnail', postThumbnail);
        }

        if (privileges.canChangeSource) {
            formData.append('source', $form.find('[name=source]').val());
        }

        if (privileges.canChangeSafety) {
            formData.append('safety', $form.find('[name=safety]:checked').val());
        }

        if (privileges.canChangeTags) {
            formData.append('tags', tagInput.getTags().join(' '));
        }

        if (privileges.canChangeRelations) {
            formData.append('relations', $form.find('[name=relations]').val());
        }

        if (privileges.canChangeFlags) {
            if (post.contentType === 'video') {
                formData.append('loop', $form.find('[name=loop]').is(':checked') ? 1 : 0);
            }
        }

        if (post.tags.length === 0) {
            showEditError('No tags set.');
            return;
        }

        jQuery(document.activeElement).blur();

        promise.wait(api.post('/posts/' + post.id, formData))
            .then(function(response) {
                tagList.refreshTags();
                post = response.json.post;
                if (typeof(updateCallback) !== 'undefined') {
                    updateCallback(post);
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

App.DI.register('postEditPresenter', ['jQuery', 'util', 'promise', 'api', 'auth', 'tagList'], App.Presenters.PostEditPresenter);
