'use strict';

const config = require('../config.js');
const views = require('../util/views.js');
const TagAutoCompleteControl = require('./tag_auto_complete_control.js');

class TagMergeView {
    constructor() {
        this.template = views.getTemplate('tag-merge');
    }

    render(ctx) {
        ctx.tagNamePattern = config.tagNameRegex;

        const target = ctx.target;
        const source = this.template(ctx);

        const form = source.querySelector('form');
        const otherTagField = source.querySelector('.target input');

        views.decorateValidator(form);
        if (otherTagField) {
            new TagAutoCompleteControl(otherTagField);
        }

        form.addEventListener('submit', e => {

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
