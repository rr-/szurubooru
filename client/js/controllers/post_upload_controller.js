'use strict';

const api = require('../api.js');
const router = require('../router.js');
const misc = require('../util/misc.js');
const topNavigation = require('../models/top_navigation.js');
const Post = require('../models/post.js');
const PostUploadView = require('../views/post_upload_view.js');
const EmptyView = require('../views/empty_view.js');

class PostUploadController {
    constructor() {
        if (!api.hasPrivilege('posts:create')) {
            this._view = new EmptyView();
            this._view.showError('You don\'t have privileges to upload posts.');
            return;
        }

        topNavigation.activate('upload');
        topNavigation.setTitle('Upload');
        this._view = new PostUploadView({
            canUploadAnonymously: api.hasPrivilege('posts:create:anonymous'),
        });
        this._view.addEventListener('change', e => this._evtChange(e));
        this._view.addEventListener('submit', e => this._evtSubmit(e));
    }

    _evtChange(e) {
        if (e.detail.uploadables.length) {
            misc.enableExitConfirmation();
        } else {
            misc.disableExitConfirmation();
        }
        this._view.clearMessages();
    }

    _evtSubmit(e) {
        this._view.disableForm();
        this._view.clearMessages();

        e.detail.uploadables.reduce((promise, uploadable) => {
            return promise.then(
                () => {
                    let post = new Post();
                    post.safety = uploadable.safety;
                    if (uploadable.url) {
                        post.newContentUrl = uploadable.url;
                    } else {
                        post.newContent = uploadable.file;
                    }
                    return post.save(uploadable.anonymous)
                        .then(() => {
                            this._view.removeUploadable(uploadable);
                            return Promise.resolve();
                        });
                });
        }, Promise.resolve()).then(
            () => {
                misc.disableExitConfirmation();
                const ctx = router.show('/posts');
                ctx.controller.showSuccess('Posts uploaded.');
            }, errorMessage => {
                this._view.showError(errorMessage);
                this._view.enableForm();
                return Promise.reject();
            });
    }
}

module.exports = router => {
    router.enter('/upload', (ctx, next) => {
        ctx.controller = new PostUploadController();
    });
};
