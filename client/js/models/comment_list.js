'use strict';

const events = require('../events.js');
const Comment = require('./comment.js');

class CommentList extends events.EventTarget {
    constructor(comments) {
        super();
        this._list = [];
    }

    static fromResponse(commentsResponse) {
        const commentList = new CommentList();
        for (let commentResponse of commentsResponse) {
            const comment = Comment.fromResponse(commentResponse);
            comment.commentList = commentList;
            commentList._list.push(comment);
        }
        return commentList;
    }

    get comments() {
        return [...this._list];
    }

    add(comment) {
        comment.commentList = this;
        this._list.push(comment);
        this.dispatchEvent(new CustomEvent('add', {
            detail: {
                comment: comment,
            },
        }));
    }

    remove(commentToRemove) {
        for (let [index, comment] of this._list.entries()) {
            if (comment.id === commentToRemove.id) {
                this._list.splice(index, 1);
                break;
            }
        }
        this.dispatchEvent(new CustomEvent('remove', {
            detail: {
                comment: commentToRemove,
            },
        }));
    }

    get length() {
        return this._list.length;
    }

    [Symbol.iterator]() {
        return this._list[Symbol.iterator]();
    }
}

module.exports = CommentList;
