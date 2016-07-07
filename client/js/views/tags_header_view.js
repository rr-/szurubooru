'use strict';

const router = require('../router.js');
const keyboard = require('../util/keyboard.js');
const misc = require('../util/misc.js');
const views = require('../util/views.js');
const TagAutoCompleteControl =
    require('../controls/tag_auto_complete_control.js');

const template = views.getTemplate('tags-header');

class TagsHeaderView {
    constructor(ctx) {
        this._hostNode = ctx.hostNode;
        views.replaceContent(this._hostNode, template(ctx));

        if (this._queryInputNode) {
            new TagAutoCompleteControl(this._queryInputNode);
        }

        keyboard.bind('q', () => {
            form.querySelector('input').focus();
        });

        this._formNode.addEventListener('submit', e => this._evtSubmit(e));
    }

    get _formNode() {
        return this._hostNode.querySelector('form');
    }

    get _queryInputNode() {
        return this._hostNode.querySelector('[name=search-text]');
    }

    _evtSubmit(e) {
        e.preventDefault();
        this._queryInputNode.blur();
        router.show(
            '/tags/' + misc.formatUrlParameters({
                query: this._queryInputNode.value,
            }));
    }
}

module.exports = TagsHeaderView;
