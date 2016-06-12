'use strict';

const views = require('../util/views.js');
const CommentListControl = require('../controls/comment_list_control.js');

class CommentsPageView {
    constructor() {
        this._template = views.getTemplate('comments-page');
    }

    render(ctx) {
        const target = ctx.target;
        const source = this._template(ctx);

        for (let post of ctx.results) {
            post.comments.sort((a, b) => { return b.id - a.id; });
            new CommentListControl(
                source.querySelector(
                    `.comments-container[data-for="${post.id}"]`),
                post.comments);
        }

        views.showView(target, source);
    }
}

module.exports = CommentsPageView;
