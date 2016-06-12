'use strict';

const router = require('../router.js');
const keyboard = require('../util/keyboard.js');
const misc = require('../util/misc.js');
const views = require('../util/views.js');
const TagAutoCompleteControl =
    require('../controls/tag_auto_complete_control.js');

class TagsHeaderView {
    constructor() {
        this._template = views.getTemplate('tags-header');
    }

    render(ctx) {
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

        form.addEventListener('submit', e => {
            e.preventDefault();
            const text = searchTextInput.value;
            searchTextInput.blur();
            router.show('/tags/' + misc.formatSearchQuery({text: text}));
        });

        views.showView(target, source);
    }
}

module.exports = TagsHeaderView;
