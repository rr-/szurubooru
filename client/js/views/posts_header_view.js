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
            this._formNode.querySelector('input:first-of-type').focus();
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
        this._formNode.addEventListener(
            'submit', e => this._evtFormSubmit(e));

        if (this._massTagInputNode) {
            if (this._openMassTagLinkNode) {
                this._openMassTagLinkNode.addEventListener(
                    'click', e => this._evtMassTagClick(e));
            }
            this._stopMassTagLinkNode.addEventListener(
                'click', e => this._evtStopTaggingClick(e));
            // this._massTagFormNode.addEventListener(
            //     'submit', e => this._evtMassTagFormSubmit(e));
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

    _evtFormSubmit(e) {
        e.preventDefault();
        let params = {
            query: this._queryInputNode.value,
            page: this._ctx.parameters.page,
        };
        if (this._massTagInputNode) {
            params.tag = this._massTagInputNode.value;
            this._massTagInputNode.blur();
        }
        router.show('/posts/' + misc.formatUrlParameters(params));
    }
}

module.exports = PostsHeaderView;
