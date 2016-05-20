'use strict';

const views = require('../util/views.js');
const TagSummaryView = require('./tag_summary_view.js');
const TagMergeView = require('./tag_merge_view.js');
const TagDeleteView = require('./tag_delete_view.js');

class TagView {
    constructor() {
        this._template = views.getTemplate('tag');
        this._summaryView = new TagSummaryView();
        this._mergeView = new TagMergeView();
        this._deleteView = new TagDeleteView();
    }

    render(ctx) {
        const target = document.getElementById('content-holder');
        const source = this._template(ctx);

        ctx.section = ctx.section || 'summary';

        for (let item of source.querySelectorAll('[data-name]')) {
            if (item.getAttribute('data-name') === ctx.section) {
                item.className = 'active';
            } else {
                item.className = '';
            }
        }

        let view = null;
        if (ctx.section == 'merge') {
            view = this._mergeView;
        } else if (ctx.section == 'delete') {
            view = this._deleteView;
        } else {
            view = this._summaryView;
        }
        ctx.target = source.querySelector('.tag-content-holder');
        view.render(ctx);

        views.listenToMessages(source);
        views.showView(target, source);
    }
}

module.exports = TagView;

