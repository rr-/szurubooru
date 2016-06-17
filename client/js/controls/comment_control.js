'use strict';

const api = require('../api.js');
const events = require('../events.js');
const views = require('../util/views.js');
const CommentFormControl = require('../controls/comment_form_control.js');

const template = views.getTemplate('comment');
const scoreTemplate = views.getTemplate('score');

class CommentControl extends events.EventTarget {
    constructor(hostNode, comment) {
        super();
        this._hostNode = hostNode;
        this._comment = comment;

        comment.addEventListener('change', e => this._evtChange(e));
        comment.addEventListener('changeScore', e => this._evtChangeScore(e));

        const isLoggedIn = api.isLoggedIn(this._comment.user);
        const infix = isLoggedIn ? 'own' : 'any';
        views.replaceContent(this._hostNode, template({
            comment: this._comment,
            canViewUsers: api.hasPrivilege('users:view'),
            canEditComment: api.hasPrivilege(`comments:edit:${infix}`),
            canDeleteComment: api.hasPrivilege(`comments:delete:${infix}`),
        }));

        if (this._editButtonNode) {
            this._editButtonNode.addEventListener(
                'click', e => this._evtEditClick(e));
        }
        if (this._deleteButtonNode) {
            this._deleteButtonNode.addEventListener(
                'click', e => this._evtDeleteClick(e));
        }

        this._formControl = new CommentFormControl(
            this._hostNode.querySelector('.comment-form-container'),
            this._comment,
            true);
        events.proxyEvent(this._formControl, this, 'submit', 'change');

        this._installScore();
    }

    get _scoreContainerNode() {
        return this._hostNode.querySelector('.score-container');
    }

    get _editButtonNode() {
        return this._hostNode.querySelector('.edit');
    }

    get _deleteButtonNode() {
        return this._hostNode.querySelector('.delete');
    }

    get _upvoteButtonNode() {
        return this._hostNode.querySelector('.upvote');
    }

    get _downvoteButtonNode() {
        return this._hostNode.querySelector('.downvote');
    }

    _installScore() {
        views.replaceContent(
            this._scoreContainerNode,
            scoreTemplate({
                score: this._comment.score,
                ownScore: this._comment.ownScore,
                canScore: api.hasPrivilege('comments:score'),
            }));

        if (this._upvoteButtonNode) {
            this._upvoteButtonNode.addEventListener(
                'click', e => this._evtScoreClick(e, 1));
        }
        if (this._downvoteButtonNode) {
            this._downvoteButtonNode.addEventListener(
                'click', e => this._evtScoreClick(e, -1));
        }
    }

    _evtEditClick(e) {
        e.preventDefault();
        this._formControl.enterEditMode();
    }

    _evtScoreClick(e, score) {
        e.preventDefault();
        this.dispatchEvent(new CustomEvent('score', {
            detail: {
                comment: this._comment,
                score: this._comment.ownScore === score ? 0 : score,
            },
        }));
    }

    _evtDeleteClick(e) {
        e.preventDefault();
        if (!window.confirm('Are you sure you want to delete this comment?')) {
            return;
        }
        this.dispatchEvent(new CustomEvent('delete', {
            detail: {
                comment: this._comment,
            },
        }));
    }

    _evtChange(e) {
        this._formControl.exitEditMode();
    }

    _evtChangeScore(e) {
        this._installScore();
    }
};

module.exports = CommentControl;
