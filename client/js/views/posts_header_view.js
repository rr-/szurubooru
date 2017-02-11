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

class BulkTagEditor extends events.EventTarget {
    constructor(hostNode) {
        super();
        this._hostNode = hostNode;

        this._autoCompleteControl = new TagAutoCompleteControl(
            this._inputNode, {addSpace: false});
        this._openLinkNode.addEventListener(
            'click', e => this._evtOpenLinkClick(e));
        this._closeLinkNode.addEventListener(
            'click', e => this._evtCloseLinkClick(e));
        this._hostNode.addEventListener('submit', e => this._evtFormSubmit(e));
    }

    get value() {
        return this._inputNode.value;
    }

    get opened() {
        return this._hostNode.classList.contains('opened');
    }

    get _openLinkNode() {
        return this._hostNode.querySelector('.open');
    }

    get _closeLinkNode() {
        return this._hostNode.querySelector('.close');
    }

    get _inputNode() {
        return this._hostNode.querySelector('input[name=tag]');
    }

    focus() {
        this._inputNode.focus();
    }

    blur() {
        this._autoCompleteControl.hide();
        this._inputNode.blur();
    }

    toggleOpen(state) {
        this._hostNode.classList.toggle('opened', state);
    }

    _evtFormSubmit(e) {
        e.preventDefault();
        this.dispatchEvent(new CustomEvent('submit', {detail: {}}));
    }

    _evtOpenLinkClick(e) {
        e.preventDefault();
        this.toggleOpen(true);
        this.focus();
        this.dispatchEvent(new CustomEvent('open', {detail: {}}));
    }

    _evtCloseLinkClick(e) {
        e.preventDefault();
        this._inputNode.value = '';
        this.toggleOpen(false);
        this.blur();
        this.dispatchEvent(new CustomEvent('close', {detail: {}}));
    }
}

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

        if (this._bulkEditTagsNode) {
            this._bulkTagEditor = new BulkTagEditor(this._bulkEditTagsNode);
            this._bulkTagEditor.toggleOpen(!!ctx.parameters.tag);
            this._bulkTagEditor.addEventListener('submit', e => {
                this._navigate();
            });
            this._bulkTagEditor.addEventListener('close', e => {
                this._navigate();
            });
        }
    }

    get _formNode() {
        return this._hostNode.querySelector('form.search');
    }

    get _safetyButtonNodes() {
        return this._hostNode.querySelectorAll('form .safety');
    }

    get _queryInputNode() {
        return this._hostNode.querySelector('form [name=search-text]');
    }

    get _bulkEditTagsNode() {
        return this._hostNode.querySelector('.bulk-edit-tags');
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
        this._navigate();
    }

    _navigate() {
        this._queryAutoCompleteControl.hide();
        let parameters = {query: this._queryInputNode.value};
        parameters.offset = parameters.query === this._ctx.parameters.query ?
            this._ctx.parameters.offset : 0;
        if (this._bulkTagEditor && this._bulkTagEditor.opened) {
            parameters.tag = this._bulkTagEditor.value;
            this._bulkTagEditor.blur();
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
