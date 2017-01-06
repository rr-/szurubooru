'use strict';

const api = require('../api.js');
const router = require('../router.js');
const misc = require('../util/misc.js');
const topNavigation = require('../models/top_navigation.js');
const Post = require('../models/post.js');
const PostUploadView = require('../views/post_upload_view.js');
const EmptyView = require('../views/empty_view.js');

const genericErrorMessage =
    'One of the posts needs your attention; ' +
    'click "resume upload" when you\'re ready.';

class PostUploadController {
    constructor() {
        this._lastCancellablePromise = null;

        if (!api.hasPrivilege('posts:create')) {
            this._view = new EmptyView();
            this._view.showError('You don\'t have privileges to upload posts.');
            return;
        }

        topNavigation.activate('upload');
        topNavigation.setTitle('Upload');
        this._view = new PostUploadView({
            canUploadAnonymously: api.hasPrivilege('posts:create:anonymous'),
            canViewPosts: api.hasPrivilege('posts:view'),
        });
        this._view.addEventListener('change', e => this._evtChange(e));
        this._view.addEventListener('submit', e => this._evtSubmit(e));
        this._view.addEventListener('cancel', e => this._evtCancel(e));
    }

    _evtChange(e) {
        if (e.detail.uploadables.length) {
            misc.enableExitConfirmation();
        } else {
            misc.disableExitConfirmation();
            this._view.clearMessages();
        }
    }

    _evtCancel(e) {
        if (this._lastCancellablePromise) {
            this._lastCancellablePromise.abort();
        }
    }

    _evtSubmit(e) {
        this._view.disableForm();
        this._view.clearMessages();

        e.detail.uploadables.reduce(
            (promise, uploadable) =>
                promise.then(() =>
                    this._uploadSinglePost(
                        uploadable, e.detail.skipDuplicates)),
            Promise.resolve())
                .then(() => {
                    this._view.clearMessages();
                    misc.disableExitConfirmation();
                    const ctx = router.show('/posts');
                    ctx.controller.showSuccess('Posts uploaded.');
                }, errorContext => {
                    if (errorContext.constructor === Array) {
                        const [errorMessage, uploadable] = errorContext;
                        this._view.showError(genericErrorMessage);
                        this._view.showError(errorMessage, uploadable);
                    } else {
                        this._view.showError(errorContext);
                    }
                    this._view.enableForm();
                    return Promise.reject();
                });
    }

    _uploadSinglePost(uploadable, skipDuplicates) {
        let post = new Post();
        post.safety = uploadable.safety;
        post.flags = uploadable.flags;

        if (uploadable.url) {
            post.newContentUrl = uploadable.url;
        } else {
            post.newContent = uploadable.file;
        }

        let savePromise = post.save(uploadable.anonymous)
            .then(() => {
                this._view.removeUploadable(uploadable);
                return Promise.resolve();
            }, errorMessage => {
                // XXX:
                // lame, API eats error codes so we need to match
                // messages instead
                if (skipDuplicates &&
                        errorMessage.match(/already uploaded/)) {
                    return Promise.resolve();
                }
                return Promise.reject([errorMessage, uploadable, null]);
            });
        this._lastCancellablePromise = savePromise;
        return savePromise;
    }
}

module.exports = router => {
    router.enter('/upload', (ctx, next) => {
        ctx.controller = new PostUploadController();
    });
};
