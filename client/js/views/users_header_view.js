'use strict';

const router = require('../router.js');
const keyboard = require('../util/keyboard.js');
const misc = require('../util/misc.js');
const views = require('../util/views.js');

const template = views.getTemplate('users-header');

class UsersHeaderView {
    constructor(ctx) {
        this._hostNode = ctx.hostNode;
        views.replaceContent(this._hostNode, template(ctx));

        keyboard.bind('q', () => {
            this._formNode.querySelector('input').focus();
        });

        this._formNode.addEventListener('submit', e => this._evtSubmit(e));
    }

    get _formNode() {
        return this._hostNode.querySelector('form');
    }

    get _queryInputNode() {
        return this._formNode.querySelector('[name=search-text]');
    }

    _evtSubmit(e) {
        e.preventDefault();
        this._queryInputNode.blur();
        router.show(
            '/users/' + misc.formatSearchQuery({
                text: this._queryInputNode.value,
            }));
    }
}

module.exports = UsersHeaderView;
