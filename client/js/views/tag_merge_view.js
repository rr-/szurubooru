'use strict';

const config = require('../config.js');
const views = require('../util/views.js');

class TagMergeView {
    constructor() {
        this.template = views.getTemplate('tag-merge');
    }

    render(ctx) {
        ctx.tagNamePattern = config.tagNameRegex;

        const target = ctx.target;
        const source = this.template(ctx);

        const form = source.querySelector('form');

        views.decorateValidator(form);

        form.addEventListener('submit', e => {
            const otherTagField = source.querySelector('.target input');

            e.preventDefault();
            views.clearMessages(target);
            views.disableForm(form);
            ctx.mergeTo(otherTagField.value)
                .catch(() => { views.enableForm(form); });
        });

        views.listenToMessages(source);
        views.showView(target, source);
    }
}

module.exports = TagMergeView;
