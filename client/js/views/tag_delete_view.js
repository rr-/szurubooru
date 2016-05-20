'use strict';

const views = require('../util/views.js');

class TagDeleteView {
    constructor() {
        this._template = views.getTemplate('tag-delete');
    }

    render(ctx) {
        const target = ctx.target;
        const source = this._template(ctx);

        const form = source.querySelector('form');

        views.decorateValidator(form);

        form.addEventListener('submit', e => {
            e.preventDefault();
            views.clearMessages(target);
            views.disableForm(form);
            ctx.delete(ctx.tag)
                .catch(() => { views.enableForm(form); });
        });

        views.listenToMessages(source);
        views.showView(target, source);
    }
}

module.exports = TagDeleteView;
