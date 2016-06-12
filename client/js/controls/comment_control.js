'use strict';

const api = require('../api.js');
const views = require('../util/views.js');
const CommentFormControl = require('../controls/comment_form_control.js');

class CommentControl {
    constructor(hostNode, comment, settings) {
        this._hostNode = hostNode;
        this._comment = comment;
        this._template = views.getTemplate('comment');
        this._scoreTemplate = views.getTemplate('score');
        this._settings = settings;

        this.install();
    }

    install() {
        const isLoggedIn = api.isLoggedIn(this._comment.user);
        const infix = isLoggedIn ? 'own' : 'any';
        const sourceNode = this._template({
            comment: this._comment,
            canViewUsers: api.hasPrivilege('users:view'),
            canEditComment: api.hasPrivilege(`comments:edit:${infix}`),
            canDeleteComment: api.hasPrivilege(`comments:delete:${infix}`),
        });

        views.showView(
            sourceNode.querySelector('.score-container'),
            this._scoreTemplate({
                score: this._comment.score,
                ownScore: this._comment.ownScore,
                canScore: api.hasPrivilege('comments:score'),
            }));

        const editButton = sourceNode.querySelector('.edit');
        const deleteButton = sourceNode.querySelector('.delete');
        const upvoteButton = sourceNode.querySelector('.upvote');
        const downvoteButton = sourceNode.querySelector('.downvote');

        if (editButton) {
            editButton.addEventListener(
                'click', e => this._evtEditClick(e));
        }
        if (deleteButton) {
            deleteButton.addEventListener(
                'click', e => this._evtDeleteClick(e));
        }

        if (upvoteButton) {
            upvoteButton.addEventListener(
                'click',
                e => this._evtScoreClick(
                    e, () => this._comment.ownScore === 1 ? 0 : 1));
        }
        if (downvoteButton) {
            downvoteButton.addEventListener(
                'click',
                e => this._evtScoreClick(
                    e, () => this._comment.ownScore === -1 ? 0 : -1));
        }

        this._formControl = new CommentFormControl(
            sourceNode.querySelector('.comment-form-container'),
            this._comment,
            {
                onSave: text => {
                    return api.put('/comment/' + this._comment.id, {
                        text: text,
                    }).then(response => {
                        this._comment = response;
                        this.install();
                    }, response => {
                        this._formControl.showError(response.description);
                    });
                },
                canCancel: true
            });

        views.showView(this._hostNode, sourceNode);
    }

    _evtScoreClick(e, scoreGetter) {
        e.preventDefault();
        api.put(
            '/comment/' + this._comment.id + '/score',
            {score: scoreGetter()})
        .then(
            response => {
                this._comment.score = parseInt(response.score);
                this._comment.ownScore = parseInt(response.ownScore);
                this.install();
            }, response => {
                window.alert(response.description);
            });
    }

    _evtEditClick(e) {
        e.preventDefault();
        this._formControl.enterEditMode();
    }

    _evtDeleteClick(e) {
        e.preventDefault();
        if (!window.confirm('Are you sure you want to delete this comment?')) {
            return;
        }
        api.delete('/comment/' + this._comment.id)
            .then(response => {
                if (this._settings.onDelete) {
                    this._settings.onDelete(this._comment);
                }
                this._hostNode.parentNode.removeChild(this._hostNode);
            }, response => {
                window.alert(response.description);
            });
    }
};

module.exports = CommentControl;
