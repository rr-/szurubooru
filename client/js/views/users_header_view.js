'use strict';

const router = require('../router.js');
const keyboard = require('../util/keyboard.js');
const misc = require('../util/misc.js');
const views = require('../util/views.js');

class UsersHeaderView {
    constructor() {
        this._template = views.getTemplate('users-header');
    }

    render(ctx) {
        const target = ctx.target;
        const source = this._template(ctx);

        const form = source.querySelector('form');

        keyboard.bind('q', () => {
            form.querySelector('input').focus();
        });

        form.addEventListener('submit', e => {
            e.preventDefault();
            const searchTextInput = form.querySelector('[name=search-text]');
            const text = searchTextInput.value;
            searchTextInput.blur();
            router.show('/users/' + misc.formatSearchQuery({text: text}));
        });

        views.showView(target, source);
    }
}

module.exports = UsersHeaderView;
