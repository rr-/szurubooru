'use strict';

const page = require('page');
const keyboard = require('../util/keyboard.js');
const misc = require('../util/misc.js');
const views = require('../util/views.js');

class TagListHeaderView {
    constructor() {
        this.template = views.getTemplate('tag-list-header');
    }

    render(ctx) {
        const target = ctx.target;
        const source = this.template(ctx);

        const form = source.querySelector('form');

        keyboard.bind('q', () => {
            form.querySelector('input').focus();
        });

        form.addEventListener('submit', e => {
            e.preventDefault();
            const searchTextInput = form.querySelector('[name=search-text]');
            const text = searchTextInput.value;
            searchTextInput.blur();
            page('/tags/' + misc.formatSearchQuery({text: text}));
        });

        views.showView(target, source);
    }
}

module.exports = TagListHeaderView;
