'use strict';

const events = require('../events.js');
const views = require('../util/views.js');
const TagSummaryView = require('./tag_summary_view.js');
const TagMergeView = require('./tag_merge_view.js');
const TagDeleteView = require('./tag_delete_view.js');

const template = views.getTemplate('tag');

class TagView extends events.EventTarget {
    constructor(ctx) {
        super();

        this._ctx = ctx;
        ctx.tag.addEventListener('change', e => this._evtChange(e));
        ctx.section = ctx.section || 'summary';

        this._hostNode = document.getElementById('content-holder');
        this._install();
    }

    _install() {
        const ctx = this._ctx;
        views.replaceContent(this._hostNode, template(ctx));

        for (let item of this._hostNode.querySelectorAll('[data-name]')) {
            item.classList.toggle(
                'active', item.getAttribute('data-name') === ctx.section);
        }

        ctx.hostNode = this._hostNode.querySelector('.tag-content-holder');
        if (ctx.section == 'merge') {
            this._view = new TagMergeView(ctx);
            this._view.addEventListener('submit', e => {
                this.dispatchEvent(
                    new CustomEvent('merge', {detail: e.detail}));
            });
        } else if (ctx.section == 'delete') {
            this._view = new TagDeleteView(ctx);
            this._view.addEventListener('submit', e => {
                this.dispatchEvent(
                    new CustomEvent('delete', {detail: e.detail}));
            });
        } else {
            this._view = new TagSummaryView(ctx);
            this._view.addEventListener('submit', e => {
                this.dispatchEvent(
                    new CustomEvent('change', {detail: e.detail}));
            });
        }
    }

    clearMessages() {
        this._view.clearMessages();
    }

    enableForm() {
        this._view.enableForm();
    }

    disableForm() {
        this._view.disableForm();
    }

    showSuccess(message) {
        this._view.showSuccess(message);
    }

    showError(message) {
        this._view.showError(message);
    }

    _evtChange(e) {
        this._ctx.tag = e.detail.tag;
        this._install(this._ctx);
    }
}

module.exports = TagView;
