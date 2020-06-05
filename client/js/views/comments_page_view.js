"use strict";

const events = require("../events.js");
const views = require("../util/views.js");
const CommentListControl = require("../controls/comment_list_control.js");

const template = views.getTemplate("comments-page");

class CommentsPageView extends events.EventTarget {
    constructor(ctx) {
        super();
        this._hostNode = ctx.hostNode;

        const sourceNode = template(ctx);

        for (let post of ctx.response.results) {
            const commentListControl = new CommentListControl(
                sourceNode.querySelector(
                    `.comments-container[data-for="${post.id}"]`
                ),
                post.comments,
                true
            );
            events.proxyEvent(commentListControl, this, "submit");
            events.proxyEvent(commentListControl, this, "score");
            events.proxyEvent(commentListControl, this, "delete");
        }

        views.replaceContent(this._hostNode, sourceNode);
    }
}

module.exports = CommentsPageView;
