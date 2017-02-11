'use strict';

const events = require('../events.js');
const settings = require('../models/settings.js');
const keyboard = require('../util/keyboard.js');
const misc = require('../util/misc.js');
const search = require('../util/search.js');
const views = require('../util/views.js');
const TagAutoCompleteControl =
    require('../controls/tag_auto_complete_control.js');

const template = views.getTemplate('posts-header');

class PostsHeaderView extends events.EventTarget {
    constructor(ctx) {
        super();

        ctx.settings = settings.get();
        this._ctx = ctx;
        this._hostNode = ctx.hostNode;
        views.replaceContent(this._hostNode, template(ctx));

        this._queryAutoCompleteControl = new TagAutoCompleteControl(
            this._queryInputNode,
            {addSpace: true, transform: misc.escapeSearchTerm});

        keyboard.bind('p', () => this._focusFirstPostNode());
        search.searchInputNodeFocusHelper(this._queryInputNode);

        for (let safetyButtonNode of this._safetyButtonNodes) {
            safetyButtonNode.addEventListener(
                'click', e => this._evtSafetyButtonClick(e));
        }
        this._formNode.addEventListener(
            'submit', e => this._evtFormSubmit(e));

        if (this._bulkEditTagsInputNode) {
            this._bulkEditTagsAutoCompleteControl = new TagAutoCompleteControl(
                this._bulkEditTagsInputNode, {addSpace: false});
            if (this._openBulkEditTagsLinkNode) {
                this._openBulkEditTagsLinkNode.addEventListener(
                    'click', e => this._evtBulkEditTagsClick(e));
            }
            this._stopBulkEditTagsLinkNode.addEventListener(
                'click', e => this._evtStopTaggingClick(e));
            this._toggleBulkEditTagsVisibility(!!ctx.parameters.tag);
        }
    }

    _toggleBulkEditTagsVisibility(state) {
        this._formNode.querySelector('.bulk-edit-tags')
            .classList.toggle('active', state);
    }

    get _formNode() {
        return this._hostNode.querySelector('form');
    }

    get _safetyButtonNodes() {
        return this._hostNode.querySelectorAll('form .safety');
    }

    get _queryInputNode() {
        return this._hostNode.querySelector('form [name=search-text]');
    }

    get _bulkEditTagsInputNode() {
        return this._hostNode.querySelector('form .bulk-edit-tags [name=tag]');
    }

    get _openBulkEditTagsLinkNode() {
        return this._hostNode.querySelector('form .bulk-edit-tags .open');
    }

    get _stopBulkEditTagsLinkNode() {
        return this._hostNode.querySelector(
            'form .bulk-edit-tags .stop-tagging');
    }

    _evtBulkEditTagsClick(e) {
        e.preventDefault();
        this._toggleBulkEditTagsVisibility(true);
    }

    _evtStopTaggingClick(e) {
        e.preventDefault();
        this._bulkEditTagsInputNode.value = '';
        this._toggleBulkEditTagsVisibility(false);
        this.dispatchEvent(new CustomEvent('navigate', {detail: {parameters: {
            query: this._ctx.parameters.query,
            offset: this._ctx.parameters.offset,
            limit: this._ctx.parameters.limit,
            tag: null,
        }}}));
    }

    _evtSafetyButtonClick(e, url) {
        e.preventDefault();
        e.target.classList.toggle('disabled');
        const safety = e.target.getAttribute('data-safety');
        let browsingSettings = settings.get();
        browsingSettings.listPosts[safety] =
            !browsingSettings.listPosts[safety];
        settings.save(browsingSettings, true);
        this.dispatchEvent(
            new CustomEvent(
                'navigate', {
                    detail: {
                        parameters: Object.assign(
                            {}, this._ctx.parameters, {tag: null, offset: 0}),
                    },
                }));
    }

    _evtFormSubmit(e) {
        e.preventDefault();
        this._queryAutoCompleteControl.hide();
        if (this._bulkEditTagsAutoCompleteControl) {
            this._bulkEditTagsAutoCompleteControl.hide();
        }
        let parameters = {query: this._queryInputNode.value};
        parameters.offset = parameters.query === this._ctx.parameters.query ?
            this._ctx.parameters.offset : 0;
        if (this._bulkEditTagsInputNode) {
            parameters.tag = this._bulkEditTagsInputNode.value;
            this._bulkEditTagsInputNode.blur();
        } else {
            parameters.tag = null;
        }
        this.dispatchEvent(
            new CustomEvent('navigate', {detail: {parameters: parameters}}));
    }

    _focusFirstPostNode() {
        const firstPostNode =
            document.body.querySelector('.post-list li:first-child a');
        if (firstPostNode) {
            firstPostNode.focus();
        }
    }
}

module.exports = PostsHeaderView;
