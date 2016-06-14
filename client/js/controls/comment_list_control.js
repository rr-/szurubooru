'use strict';

const api = require('../api.js');
const views = require('../util/views.js');
const CommentControl = require('../controls/comment_control.js');

class CommentListControl {
    constructor(hostNode, comments) {
        this._hostNode = hostNode;
        this._comments = comments;
        this._template = views.getTemplate('comment-list');

        this.install();
    }

    install() {
        const sourceNode = this._template({
            comments: this._comments,
            canListComments: api.hasPrivilege('comments:list'),
        });

        views.replaceContent(this._hostNode, sourceNode);

        this._renderComments();
    }

    _renderComments() {
        if (!this._comments.length) {
            return;
        }
        const commentList = new DocumentFragment();
        for (let comment of this._comments) {
            const commentListItemNode = document.createElement('li');
            new CommentControl(commentListItemNode, comment, {
                onDelete: removedComment => {
                    for (let [index, comment] of this._comments.entries()) {
                        if (comment.id === removedComment.id) {
                            this._comments.splice(index, 1);
                        }
                    }
                },
            });
            commentList.appendChild(commentListItemNode);
        }
        views.replaceContent(this._hostNode.querySelector('ul'), commentList);
    }
};

module.exports = CommentListControl;
