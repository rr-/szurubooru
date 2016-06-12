'use strict';

const router = require('../router.js');
const settings = require('../settings.js');
const keyboard = require('../util/keyboard.js');
const misc = require('../util/misc.js');
const views = require('../util/views.js');
const TagAutoCompleteControl =
    require('../controls/tag_auto_complete_control.js');

class PostsHeaderView {
    constructor() {
        this._template = views.getTemplate('posts-header');
    }

    render(ctx) {
        ctx.settings = settings.getSettings();

        const target = ctx.target;
        const source = this._template(ctx);

        const form = source.querySelector('form');
        const searchTextInput = form.querySelector('[name=search-text]');

        if (searchTextInput) {
            new TagAutoCompleteControl(searchTextInput);
        }

        keyboard.bind('q', () => {
            form.querySelector('input').focus();
        });

        keyboard.bind('p', () => {
            const firstPostNode
                = document.body.querySelector('.post-list li:first-child a');
            if (firstPostNode) {
                firstPostNode.focus();
            }
        });

        for (let safetyButton of form.querySelectorAll('.safety')) {
            safetyButton.addEventListener(
                'click', e => this._evtSafetyButtonClick(e, ctx.clientUrl));
        }
        form.addEventListener(
            'submit', e => this._evtFormSubmit(e, searchTextInput));

        views.showView(target, source);
    }

    _evtSafetyButtonClick(e, url) {
        e.preventDefault();
        e.target.classList.toggle('disabled');
        const safety = e.target.getAttribute('data-safety');
        let browsingSettings = settings.getSettings();
        browsingSettings.listPosts[safety]
            = !browsingSettings.listPosts[safety];
        settings.saveSettings(browsingSettings, true);
        router.show(url.replace(/{page}/, 1));
    }

    _evtFormSubmit(e, searchTextInput) {
        e.preventDefault();
        const text = searchTextInput.value;
        searchTextInput.blur();
        router.show('/posts/' + misc.formatSearchQuery({text: text}));
    }
}

module.exports = PostsHeaderView;
