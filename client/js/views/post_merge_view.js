'use strict';

const config = require('../config.js');
const events = require('../events.js');
const views = require('../util/views.js');

const KEY_RETURN = 13;
const template = views.getTemplate('post-merge');
const sideTemplate = views.getTemplate('post-merge-side');

class PostMergeView extends events.EventTarget {
    constructor(ctx) {
        super();

        this._ctx = ctx;
        this._post = ctx.post;
        this._hostNode = ctx.hostNode;

        this._leftPost = ctx.post;
        this._rightPost = null;
        views.replaceContent(this._hostNode, template(this._ctx));
        views.decorateValidator(this._formNode);

        this._refreshLeftSide();
        this._refreshRightSide();

        this._formNode.addEventListener('submit', e => this._evtSubmit(e));
    }

    clearMessages() {
        views.clearMessages(this._hostNode);
    }

    enableForm() {
        views.enableForm(this._formNode);
    }

    disableForm() {
        views.disableForm(this._formNode);
    }

    showSuccess(message) {
        views.showSuccess(this._hostNode, message);
    }

    showError(message) {
        views.showError(this._hostNode, message);
    }

    selectPost(post) {
        this._rightPost = post;
        this._refreshRightSide();
    }

    _refreshLeftSide() {
        views.replaceContent(
            this._leftSideNode,
            sideTemplate(Object.assign({}, this._ctx, {
                post: this._leftPost,
                name: 'left',
                editable: false})));
    }

    _refreshRightSide() {
        views.replaceContent(
            this._rightSideNode,
            sideTemplate(Object.assign({}, this._ctx, {
                post: this._rightPost,
                name: 'right',
                editable: true})));

        if (this._targetPostFieldNode) {
            this._targetPostFieldNode.addEventListener(
                'keydown', e => this._evtTargetPostFieldKeyDown(e));
        }
    }

    _evtSubmit(e) {
        e.preventDefault();
        const checkedTargetPost = this._formNode.querySelector(
            '.target-post :checked').value;
        const checkedTargetPostContent = this._formNode.querySelector(
            '.target-post-content :checked').value;
        this.dispatchEvent(new CustomEvent('submit', {
            detail: {
                post: checkedTargetPost == 'left' ?
                    this._rightPost :
                    this._leftPost,
                targetPost: checkedTargetPost == 'left' ?
                    this._leftPost :
                    this._rightPost,
                useOldContent: checkedTargetPostContent !== checkedTargetPost,
            },
        }));
    }

    _evtTargetPostFieldKeyDown(e) {
        const key = e.which;
        if (key !== KEY_RETURN) {
            return;
        }
        e.target.blur();
        e.preventDefault();
        this.dispatchEvent(new CustomEvent('select', {
            detail: {
                postId: this._targetPostFieldNode.value,
            },
        }));
    }

    get _formNode() {
        return this._hostNode.querySelector('form');
    }

    get _leftSideNode() {
        return this._hostNode.querySelector('.left-post-container');
    }

    get _rightSideNode() {
        return this._hostNode.querySelector('.right-post-container');
    }

    get _targetPostFieldNode() {
        return this._formNode.querySelector(
            '.post-mirror input:not([readonly])[type=text]');
    }
}

module.exports = PostMergeView;
