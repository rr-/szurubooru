'use strict';

const events = require('../events.js');
const views = require('../util/views.js');

const template = views.getTemplate('posts-page');

class PostsPageView extends events.EventTarget {
    constructor(ctx) {
        super();
        this._ctx = ctx;
        this._hostNode = ctx.hostNode;
        views.replaceContent(this._hostNode, template(ctx));

        this._postIdToPost = {};
        for (let post of ctx.response.results) {
            this._postIdToPost[post.id] = post;
            post.addEventListener('change', e => this._evtPostChange(e));
        }

        this._postIdToLinkNode = {};
        for (let linkNode of this._tagFlipperNodes) {
            const postId = linkNode.getAttribute('data-post-id');
            const post = this._postIdToPost[postId];
            this._postIdToLinkNode[postId] = linkNode;
            linkNode.addEventListener(
                'click', e => this._evtBulkEditTagsClick(e, post));
        }

        this._syncTagFlippersHighlights();
    }

    get _tagFlipperNodes() {
        return this._hostNode.querySelectorAll('.tag-flipper');
    }

    _evtPostChange(e) {
        const linkNode = this._postIdToLinkNode[e.detail.post.id];
        linkNode.removeAttribute('data-disabled');
        this._syncTagFlippersHighlights();
    }

    _evtBulkEditTagsClick(e, post) {
        e.preventDefault();
        const linkNode = e.target;
        if (linkNode.getAttribute('data-disabled')) {
            return;
        }
        linkNode.setAttribute('data-disabled', true);
        this.dispatchEvent(
            new CustomEvent(
                linkNode.classList.contains('tagged') ? 'untag' : 'tag',
                {detail: {post: post}}));
    }

    _syncTagFlippersHighlights() {
        for (let linkNode of this._tagFlipperNodes) {
            const postId = linkNode.getAttribute('data-post-id');
            const post = this._postIdToPost[postId];
            let tagged = true;
            for (let tag of this._ctx.bulkEdit.tags) {
                tagged = tagged & post.isTaggedWith(tag);
            }
            linkNode.classList.toggle('tagged', tagged);
        }
    }
}

module.exports = PostsPageView;
