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
        for (let linkNode of this._hostNode.querySelectorAll('.masstag')) {
            const postId = linkNode.getAttribute('data-post-id');
            const post = this._postIdToPost[postId];
            this._postIdToLinkNode[postId] = linkNode;
            linkNode.addEventListener(
                'click', e => this._evtMassTagClick(e, post));
        }

        this._syncMassTagHighlights();
    }

    _evtPostChange(e) {
        const linkNode = this._postIdToLinkNode[e.detail.post.id];
        linkNode.removeAttribute('data-disabled');
        this._syncMassTagHighlights();
    }

    _syncMassTagHighlights() {
        for (let linkNode of this._hostNode.querySelectorAll('.masstag')) {
            const postId = linkNode.getAttribute('data-post-id');
            const post = this._postIdToPost[postId];
            let tagged = true;
            for (let tag of this._ctx.massTagTags) {
                tagged = tagged & post.isTaggedWith(tag);
            }
            linkNode.classList.toggle('tagged', tagged);
        }
    }

    _evtMassTagClick(e, post) {
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
}

module.exports = PostsPageView;
