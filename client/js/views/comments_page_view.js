'use strict';

const views = require('../util/views.js');
const CommentListControl = require('../controls/comment_list_control.js');

const template = views.getTemplate('comments-page');

class CommentsPageView {
    constructor(ctx) {
        this._hostNode = ctx.hostNode;
        this._controls = [];

        const sourceNode = template(ctx);

        for (let post of ctx.results) {
            post.comments.sort((a, b) => { return b.id - a.id; });
            this._controls.push(
                new CommentListControl(
                    sourceNode.querySelector(
                        `.comments-container[data-for="${post.id}"]`),
                    post.comments));
        }

        views.replaceContent(this._hostNode, sourceNode);
    }
}

module.exports = CommentsPageView;
