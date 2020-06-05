"use strict";

const events = require("../events.js");
const views = require("../util/views.js");
const CommentControl = require("../controls/comment_control.js");

const template = views.getTemplate("comment-list");

class CommentListControl extends events.EventTarget {
    constructor(hostNode, comments, reversed) {
        super();
        this._hostNode = hostNode;
        this._comments = comments;
        this._commentIdToNode = {};

        comments.addEventListener("add", (e) => this._evtAdd(e));
        comments.addEventListener("remove", (e) => this._evtRemove(e));

        views.replaceContent(this._hostNode, template());

        const commentList = Array.from(comments);
        if (reversed) {
            commentList.reverse();
        }
        for (let comment of commentList) {
            this._installCommentNode(comment);
        }
    }

    get _commentListNode() {
        return this._hostNode.querySelector("ul");
    }

    _installCommentNode(comment) {
        const commentListItemNode = document.createElement("li");
        const commentControl = new CommentControl(
            commentListItemNode,
            comment,
            false
        );
        events.proxyEvent(commentControl, this, "submit");
        events.proxyEvent(commentControl, this, "score");
        events.proxyEvent(commentControl, this, "delete");
        this._commentIdToNode[comment.id] = commentListItemNode;
        this._commentListNode.appendChild(commentListItemNode);
    }

    _uninstallCommentNode(comment) {
        const commentListItemNode = this._commentIdToNode[comment.id];
        commentListItemNode.parentNode.removeChild(commentListItemNode);
    }

    _evtAdd(e) {
        this._installCommentNode(e.detail.comment);
    }

    _evtRemove(e) {
        this._uninstallCommentNode(e.detail.comment);
    }
}

module.exports = CommentListControl;
