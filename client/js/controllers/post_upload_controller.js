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
                promise.then(() => this._uploadSinglePost(
                    uploadable, e.detail.skipDuplicates)),
            Promise.resolve())
                .then(() => {
                    this._view.clearMessages();
                    misc.disableExitConfirmation();
                    const ctx = router.show('/posts');
                    ctx.controller.showSuccess('Posts uploaded.');
                }, ([errorMessage, uploadable, similarPostResults]) => {
                    if (uploadable) {
                        if (similarPostResults) {
                            uploadable.lookalikes = similarPostResults;
                            this._view.updateUploadable(uploadable);
                            this._view.showInfo(genericErrorMessage);
                            this._view.showInfo(errorMessage, uploadable);
                        } else {
                            this._view.showError(genericErrorMessage);
                            this._view.showError(errorMessage, uploadable);
                        }
                    } else {
                        this._view.showError(errorMessage);
                    }
                    this._view.enableForm();
                    return Promise.reject();
                });
    }

    _uploadSinglePost(uploadable, skipDuplicates) {
        let reverseSearchPromise = Promise.resolve();
        if (!uploadable.lookalikesConfirmed &&
                ['image'].includes(uploadable.type)) {
            reverseSearchPromise =
                Post.reverseSearch(uploadable.url || uploadable.file);
        }
        this._lastCancellablePromise = reverseSearchPromise;

        return reverseSearchPromise.then(searchResult => {
            if (searchResult) {
                // notify about exact duplicate
                if (searchResult.exactPost && !skipDuplicates) {
                    return Promise.reject([
                        `Post already uploaded (@${searchResult.exactPost.id})`,
                        uploadable,
                        null]);
                }

                // notify about similar posts
                if (!searchResult.exactPost
                        && searchResult.similarPosts.length) {
                    return Promise.reject([
                        `Found ${searchResult.similarPosts.length} similar ` +
                        'posts.\nYou can resume or discard this upload.',
                        uploadable,
                        searchResult.similarPosts]);
                }
            }

            // no duplicates, proceed with saving
            let post = this._uploadableToPost(uploadable);
            let apiSavePromise = post.save(uploadable.anonymous);
            let returnedSavePromise = apiSavePromise
                .then(() => {
                    this._view.removeUploadable(uploadable);
                    return Promise.resolve();
                }, errorMessage => {
                    return Promise.reject([errorMessage, uploadable, null]);
                });

            returnedSavePromise.abort = () => {
                apiSavePromise.abort();
            };

            this._lastCancellablePromise = returnedSavePromise;
            return returnedSavePromise;
        }, errorMessage => {
            return Promise.reject([errorMessage, uploadable, null]);
        });
    }

    _uploadableToPost(uploadable) {
        let post = new Post();
        post.safety = uploadable.safety;
        post.flags = uploadable.flags;
        post.tags = uploadable.tags;
        post.relations = uploadable.relations;
        post.newContent = uploadable.url || uploadable.file;
        return post;
    }
}

module.exports = router => {
    router.enter('/upload', (ctx, next) => {
        ctx.controller = new PostUploadController();
    });
};
