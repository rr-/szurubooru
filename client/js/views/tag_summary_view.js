'use strict';

const config = require('../config.js');
const views = require('../util/views.js');

function split(str) {
    return str.split(/\s+/).filter(s => s);
}

class TagSummaryView {
    constructor() {
        this.template = views.getTemplate('tag-summary');
    }

    render(ctx) {
        const baseRegex = config.tagNameRegex.replace(/[\^\$]/g, '');
        ctx.tagNamesPattern = '^((' + baseRegex + ')\\s+)*(' + baseRegex + ')$';

        const target = ctx.target;
        const source = this.template(ctx);

        const form = source.querySelector('form');

        views.decorateValidator(form);

        form.addEventListener('submit', e => {
            const namesField = source.querySelector('.names input');
            const categoryField = source.querySelector('.category select');
            const implicationsField =
                source.querySelector('.implications input');
            const suggestionsField = source.querySelector('.suggestions input');

            e.preventDefault();
            views.clearMessages(target);
            views.disableForm(form);
            ctx.save({
                names: split(namesField.value),
                category: categoryField.value,
                implications: split(implicationsField.value),
                suggestions: split(suggestionsField.value),
            }).always(() => { views.enableForm(form); });
        });

        views.listenToMessages(source);
        views.showView(target, source);
    }
}

module.exports = TagSummaryView;
