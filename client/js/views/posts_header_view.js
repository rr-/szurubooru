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
        this._hostNode = ctx.hostNode;
        views.replaceContent(this._hostNode, template(ctx));

        if (this._queryInputNode) {
            new TagAutoCompleteControl(this._queryInputNode);
        }

        keyboard.bind('q', () => {
            this._formNode.querySelector('input').focus();
        });

        keyboard.bind('p', () => {
            const firstPostNode =
                document.body.querySelector('.post-list li:first-child a');
            if (firstPostNode) {
                firstPostNode.focus();
            }
        });

        for (let safetyButton of this._formNode.querySelectorAll('.safety')) {
            safetyButton.addEventListener(
                'click', e => this._evtSafetyButtonClick(e, ctx.clientUrl));
        }
        this._formNode.addEventListener(
            'submit', e => this._evtFormSubmit(e, this._queryInputNode));
    }

    get _formNode() {
        return this._hostNode.querySelector('form');
    }

    get _queryInputNode() {
        return this._formNode.querySelector('[name=search-text]');
    }

    _evtSafetyButtonClick(e, url) {
        e.preventDefault();
        e.target.classList.toggle('disabled');
        const safety = e.target.getAttribute('data-safety');
        let browsingSettings = settings.get();
        browsingSettings.listPosts[safety] =
            !browsingSettings.listPosts[safety];
        settings.save(browsingSettings, true);
        router.show(url.replace(/{page}/, 1));
    }

    _evtFormSubmit(e, queryInputNode) {
        e.preventDefault();
        const text = queryInputNode.value;
        queryInputNode.blur();
        router.show('/posts/' + misc.formatSearchQuery({text: text}));
    }
}

module.exports = PostsHeaderView;
