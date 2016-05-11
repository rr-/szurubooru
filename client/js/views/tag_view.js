'use strict';

const views = require('../util/views.js');
const TagSummaryView = require('./tag_summary_view.js');
const TagMergeView = require('./tag_merge_view.js');
const TagDeleteView = require('./tag_delete_view.js');

class TagView {
    constructor() {
        this.template = views.getTemplate('tag');
        this.summaryView = new TagSummaryView();
        this.mergeView = new TagMergeView();
        this.deleteView = new TagDeleteView();
    }

    render(ctx) {
        const target = document.getElementById('content-holder');
        const source = this.template(ctx);

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
            view = this.mergeView;
        } else if (ctx.section == 'delete') {
            view = this.deleteView;
        } else {
            view = this.summaryView;
        }
        ctx.target = source.querySelector('.tag-content-holder');
        view.render(ctx);

        views.listenToMessages(source);
        views.showView(target, source);
    }
}

module.exports = TagView;

