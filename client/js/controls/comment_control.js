"use strict";

const api = require("../api.js");
const misc = require("../util/misc.js");
const events = require("../events.js");
const views = require("../util/views.js");

const template = views.getTemplate("comment");
const scoreTemplate = views.getTemplate("score");

class CommentControl extends events.EventTarget {
    constructor(hostNode, comment, onlyEditing) {
        super();
        this._hostNode = hostNode;
        this._comment = comment;
        this._onlyEditing = onlyEditing;

        if (comment) {
            comment.addEventListener("change", (e) => this._evtChange(e));
            comment.addEventListener("changeScore", (e) =>
                this._evtChangeScore(e)
            );
        }

        const isLoggedIn = comment && api.isLoggedIn(comment.user);
        const infix = isLoggedIn ? "own" : "any";
        views.replaceContent(
            this._hostNode,
            template({
                comment: comment,
                user: comment ? comment.user : api.user,
                canViewUsers: api.hasPrivilege("users:view"),
                canEditComment: api.hasPrivilege(`comments:edit:${infix}`),
                canDeleteComment: api.hasPrivilege(`comments:delete:${infix}`),
                onlyEditing: onlyEditing,
            })
        );

        if (this._editButtonNodes) {
            for (let node of this._editButtonNodes) {
                node.addEventListener("click", (e) => this._evtEditClick(e));
            }
        }
        if (this._deleteButtonNode) {
            this._deleteButtonNode.addEventListener("click", (e) =>
                this._evtDeleteClick(e)
            );
        }

        if (this._previewEditingButtonNode) {
            this._previewEditingButtonNode.addEventListener("click", (e) =>
                this._evtPreviewEditingClick(e)
            );
        }

        if (this._saveChangesButtonNode) {
            this._saveChangesButtonNode.addEventListener("click", (e) =>
                this._evtSaveChangesClick(e)
            );
        }

        if (this._cancelEditingButtonNode) {
            this._cancelEditingButtonNode.addEventListener("click", (e) =>
                this._evtCancelEditingClick(e)
            );
        }

        this._installScore();
        if (onlyEditing) {
            this._selectNav("edit");
            this._selectTab("edit");
        } else {
            this._selectNav("readonly");
            this._selectTab("preview");
        }
    }

    get _formNode() {
        return this._hostNode.querySelector("form");
    }

    get _scoreContainerNode() {
        return this._hostNode.querySelector(".score-container");
    }

    get _editButtonNodes() {
        return this._hostNode.querySelectorAll("li.edit>a, a.edit");
    }

    get _previewEditingButtonNode() {
        return this._hostNode.querySelector("li.preview>a");
    }

    get _deleteButtonNode() {
        return this._hostNode.querySelector(".delete");
    }

    get _upvoteButtonNode() {
        return this._hostNode.querySelector(".upvote");
    }

    get _downvoteButtonNode() {
        return this._hostNode.querySelector(".downvote");
    }

    get _saveChangesButtonNode() {
        return this._hostNode.querySelector(".save-changes");
    }

    get _cancelEditingButtonNode() {
        return this._hostNode.querySelector(".cancel-editing");
    }

    get _textareaNode() {
        return this._hostNode.querySelector(".tab.edit textarea");
    }

    get _contentNode() {
        return this._hostNode.querySelector(".tab.preview .comment-content");
    }

    get _heightKeeperNode() {
        return this._hostNode.querySelector(".keep-height");
    }

    _installScore() {
        views.replaceContent(
            this._scoreContainerNode,
            scoreTemplate({
                score: this._comment ? this._comment.score : 0,
                ownScore: this._comment ? this._comment.ownScore : 0,
                canScore: api.hasPrivilege("comments:score"),
            })
        );

        if (this._upvoteButtonNode) {
            this._upvoteButtonNode.addEventListener("click", (e) =>
                this._evtScoreClick(e, 1)
            );
        }
        if (this._downvoteButtonNode) {
            this._downvoteButtonNode.addEventListener("click", (e) =>
                this._evtScoreClick(e, -1)
            );
        }
    }

    enterEditMode() {
        this._selectNav("edit");
        this._selectTab("edit");
    }

    exitEditMode() {
        if (this._onlyEditing) {
            this._selectNav("edit");
            this._selectTab("edit");
            this._setText("");
        } else {
            this._selectNav("readonly");
            this._selectTab("preview");
            this._setText(this._comment.text);
        }
        this._forgetHeight();
        views.clearMessages(this._hostNode);
    }

    enableForm() {
        views.enableForm(this._formNode);
    }

    disableForm() {
        views.disableForm(this._formNode);
    }

    showError(message) {
        views.showError(this._hostNode, message);
    }

    _evtEditClick(e) {
        e.preventDefault();
        this.enterEditMode();
    }

    _evtScoreClick(e, score) {
        e.preventDefault();
        if (!api.hasPrivilege("comments:score")) {
            return;
        }
        this.dispatchEvent(
            new CustomEvent("score", {
                detail: {
                    comment: this._comment,
                    score: this._comment.ownScore === score ? 0 : score,
                },
            })
        );
    }

    _evtDeleteClick(e) {
        e.preventDefault();
        if (!window.confirm("Are you sure you want to delete this comment?")) {
            return;
        }
        this.dispatchEvent(
            new CustomEvent("delete", {
                detail: {
                    comment: this._comment,
                },
            })
        );
    }

    _evtChange(e) {
        this.exitEditMode();
    }

    _evtChangeScore(e) {
        this._installScore();
    }

    _evtPreviewEditingClick(e) {
        e.preventDefault();
        this._contentNode.innerHTML = misc.formatMarkdown(
            this._textareaNode.value
        );
        this._selectTab("edit");
        this._selectTab("preview");
    }

    _evtSaveChangesClick(e) {
        e.preventDefault();
        this.dispatchEvent(
            new CustomEvent("submit", {
                detail: {
                    target: this,
                    comment: this._comment,
                    text: this._textareaNode.value,
                },
            })
        );
    }

    _evtCancelEditingClick(e) {
        e.preventDefault();
        this.exitEditMode();
    }

    _setText(text) {
        this._textareaNode.value = text;
        this._contentNode.innerHTML = misc.formatMarkdown(text);
    }

    _selectNav(modeName) {
        for (let node of this._hostNode.querySelectorAll("nav")) {
            node.classList.toggle("active", node.classList.contains(modeName));
        }
    }

    _selectTab(tabName) {
        this._ensureHeight();

        for (let node of this._hostNode.querySelectorAll(".tab, .tabs li")) {
            node.classList.toggle("active", node.classList.contains(tabName));
        }
    }

    _ensureHeight() {
        this._heightKeeperNode.style.minHeight =
            this._heightKeeperNode.getBoundingClientRect().height + "px";
    }

    _forgetHeight() {
        this._heightKeeperNode.style.minHeight = null;
    }
}

module.exports = CommentControl;
