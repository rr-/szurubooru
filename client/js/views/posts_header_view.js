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
        if (this._massTagInputNode) {
            this._masstagAutoCompleteControl = new TagAutoCompleteControl(
                this._massTagInputNode, {addSpace: false});
        }

        keyboard.bind('p', () => this._focusFirstPostNode());
        search.searchInputNodeFocusHelper(this._queryInputNode);

        for (let safetyButtonNode of this._safetyButtonNodes) {
            safetyButtonNode.addEventListener(
                'click', e => this._evtSafetyButtonClick(e));
        }
        this._formNode.addEventListener(
            'submit', e => this._evtFormSubmit(e));

        if (this._massTagInputNode) {
            if (this._openMassTagLinkNode) {
                this._openMassTagLinkNode.addEventListener(
                    'click', e => this._evtMassTagClick(e));
            }
            this._stopMassTagLinkNode.addEventListener(
                'click', e => this._evtStopTaggingClick(e));
            this._toggleMassTagVisibility(!!ctx.parameters.tag);
        }
    }

    _toggleMassTagVisibility(state) {
        this._formNode.querySelector('.masstag')
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

    get _massTagInputNode() {
        return this._hostNode.querySelector('form [name=masstag]');
    }

    get _openMassTagLinkNode() {
        return this._hostNode.querySelector('form .open-masstag');
    }

    get _stopMassTagLinkNode() {
        return this._hostNode.querySelector('form .stop-tagging');
    }

    _evtMassTagClick(e) {
        e.preventDefault();
        this._toggleMassTagVisibility(true);
    }

    _evtStopTaggingClick(e) {
        e.preventDefault();
        this._massTagInputNode.value = '';
        this._toggleMassTagVisibility(false);
        this.dispatchEvent(new CustomEvent('navigate', {detail: {parameters: {
            query: this._ctx.parameters.query,
            page: this._ctx.parameters.page,
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
                            {}, this._ctx.parameters, {tag: null, page: 1}),
                    },
                }));
    }

    _evtFormSubmit(e) {
        e.preventDefault();
        this._queryAutoCompleteControl.hide();
        if (this._masstagAutoCompleteControl) {
            this._masstagAutoCompleteControl.hide();
        }
        let parameters = {query: this._queryInputNode.value};
        parameters.page = parameters.query === this._ctx.parameters.query ?
            this._ctx.parameters.page : 1;
        if (this._massTagInputNode) {
            parameters.tag = this._massTagInputNode.value;
            this._massTagInputNode.blur();
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
