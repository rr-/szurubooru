'use strict';

const router = require('../router.js');
const settings = require('../models/settings.js');
const keyboard = require('../util/keyboard.js');
const misc = require('../util/misc.js');
const views = require('../util/views.js');
const TagAutoCompleteControl =
    require('../controls/tag_auto_complete_control.js');

const template = views.getTemplate('posts-header');

class PostsHeaderView {
    constructor(ctx) {
        ctx.settings = settings.get();
        this._ctx = ctx;
        this._hostNode = ctx.hostNode;
        views.replaceContent(this._hostNode, template(ctx));

        if (this._queryInputNode) {
            new TagAutoCompleteControl(this._queryInputNode, {addSpace: true});
        }
        if (this._massTagInputNode) {
            new TagAutoCompleteControl(
                this._massTagInputNode, {addSpace: false});
        }

        keyboard.bind('q', () => {
            this._searchFormNode.querySelector('input').focus();
        });

        keyboard.bind('p', () => {
            const firstPostNode =
                document.body.querySelector('.post-list li:first-child a');
            if (firstPostNode) {
                firstPostNode.focus();
            }
        });

        for (let safetyButtonNode of this._safetyButtonNodes) {
            safetyButtonNode.addEventListener(
                'click', e => this._evtSafetyButtonClick(e));
        }
        this._searchFormNode.addEventListener(
            'submit', e => this._evtSearchFormSubmit(e));

        if (this._massTagFormNode) {
            if (this._openMassTagLinkNode) {
                this._openMassTagLinkNode.addEventListener(
                    'click', e => this._evtMassTagClick(e));
            }
            this._stopMassTagLinkNode.addEventListener(
                'click', e => this._evtStopTaggingClick(e));
            this._massTagFormNode.addEventListener(
                'submit', e => this._evtMassTagFormSubmit(e));
            this._toggleMassTagVisibility(!!ctx.parameters.tag);
        }
    }

    _toggleMassTagVisibility(state) {
        this._massTagFormNode.classList.toggle('active', state);
    }

    get _searchFormNode() {
        return this._hostNode.querySelector('form.search');
    }

    get _massTagFormNode() {
        return this._hostNode.querySelector('form.masstag');
    }

    get _safetyButtonNodes() {
        return this._hostNode.querySelectorAll('form.search .safety');
    }

    get _queryInputNode() {
        return this._hostNode.querySelector('form.search [name=search-text]');
    }

    get _massTagInputNode() {
        return this._hostNode.querySelector('form.masstag [type=text]');
    }

    get _openMassTagLinkNode() {
        return this._hostNode.querySelector('form.masstag .open-masstag');
    }

    get _stopMassTagLinkNode() {
        return this._hostNode.querySelector('form.masstag .stop-tagging');
    }

    _evtMassTagClick(e) {
        e.preventDefault();
        this._toggleMassTagVisibility(true);
    }

    _evtStopTaggingClick(e) {
        e.preventDefault();
        router.show('/posts/' + misc.formatUrlParameters({
            query: this._ctx.parameters.query,
            page: this._ctx.parameters.page,
        }));
    }

    _evtSafetyButtonClick(e, url) {
        e.preventDefault();
        e.target.classList.toggle('disabled');
        const safety = e.target.getAttribute('data-safety');
        let browsingSettings = settings.get();
        browsingSettings.listPosts[safety] =
            !browsingSettings.listPosts[safety];
        settings.save(browsingSettings, true);
        router.show(router.url);
    }

    _evtSearchFormSubmit(e) {
        e.preventDefault();
        const text = this._queryInputNode.value;
        this._queryInputNode.blur();
        router.show('/posts/' + misc.formatUrlParameters({query: text}));
    }

    _evtMassTagFormSubmit(e) {
        e.preventDefault();
        const text = this._queryInputNode.value;
        const tag = this._massTagInputNode.value;
        this._massTagInputNode.blur();
        router.show('/posts/' + misc.formatUrlParameters({
            query: text,
            tag: tag,
            page: this._ctx.parameters.page,
        }));
    }
}

module.exports = PostsHeaderView;
