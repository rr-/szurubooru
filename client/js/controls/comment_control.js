'use strict';

const api = require('../api.js');
const misc = require('../util/misc.js');
const views = require('../util/views.js');

class CommentControl {
    constructor(hostNode, comment) {
        this._hostNode = hostNode;
        this._comment = comment;
        this._template = views.getTemplate('comment');
        this._scoreTemplate = views.getTemplate('score');

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
        const previewTabButton = sourceNode.querySelector('.buttons .preview');
        const editTabButton = sourceNode.querySelector('.buttons .edit');
        const formNode = sourceNode.querySelector('form');
        const cancelButton = sourceNode.querySelector('.cancel');
        const textareaNode = sourceNode.querySelector('form textarea');

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

        previewTabButton.addEventListener(
            'click', e => this._evtPreviewClick(e));
        editTabButton.addEventListener(
            'click', e => this._evtEditClick(e));

        formNode.addEventListener('submit', e => this._evtSaveClick(e));
        cancelButton.addEventListener('click', e => this._evtCancelClick(e));

        for (let event of ['cut', 'paste', 'drop', 'keydown']) {
            textareaNode.addEventListener(event, e => {
                window.setTimeout(() => this._growTextArea(), 0);
            });
        }
        textareaNode.addEventListener('change', e => { this._growTextArea(); });

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

    _evtDeleteClick(e) {
        e.preventDefault();
        if (!window.confirm('Are you sure you want to delete this comment?')) {
            return;
        }
        api.delete('/comment/' + this._comment.id)
            .then(response => {
                this._hostNode.parentNode.removeChild(this._hostNode);
            }, response => {
                window.alert(response.description);
            });
    }

    _evtSaveClick(e) {
        e.preventDefault();
        api.put('/comment/' + this._comment.id, {
            text: this._hostNode.querySelector('.edit.tab textarea').value,
        }).then(response => {
            this._comment = response;
            this.install();
        }, response => {
            this._showError(response.description);
        });
    }

    _evtPreviewClick(e) {
        e.preventDefault();
        this._hostNode.querySelector('.preview.tab .content').innerHTML
            = misc.formatMarkdown(
                this._hostNode.querySelector('.edit.tab textarea').value);
        this._freezeTabHeights();
        this._selectTab('preview');
    }

    _evtEditClick(e) {
        e.preventDefault();
        this._freezeTabHeights();
        this._enterEditMode();
        this._selectTab('edit');
        this._growTextArea();
    }

    _evtCancelClick(e) {
        e.preventDefault();
        this._exitEditMode();
        this._hostNode.querySelector('.edit.tab textarea').value
            = this._comment.text;
    }

    _enterEditMode() {
        this._hostNode.querySelector('.comment').classList.add('editing');
        misc.enableExitConfirmation();
    }

    _exitEditMode() {
        this._hostNode.querySelector('.comment').classList.remove('editing');
        this._hostNode.querySelector('.tabs-wrapper').style.minHeight = null;
        misc.disableExitConfirmation();
        views.clearMessages(this._hostNode);
    }

    _selectTab(tabName) {
        this._freezeTabHeights();
        for (let tab of this._hostNode.querySelectorAll('.tab, .buttons li')) {
            tab.classList.toggle('active', tab.classList.contains(tabName));
        }
    }

    _freezeTabHeights() {
        const tabsNode = this._hostNode.querySelector('.tabs-wrapper');
        const tabsHeight = tabsNode.getBoundingClientRect().height;
        tabsNode.style.minHeight = tabsHeight + 'px';
    }

    _growTextArea() {
        const previewNode = this._hostNode.querySelector('.content');
        const textareaNode = this._hostNode.querySelector('textarea');
        textareaNode.style.height = textareaNode.scrollHeight + 'px';
    }

    _showError(message) {
        views.showError(this._hostNode, message);
    }
};

module.exports = CommentControl;
