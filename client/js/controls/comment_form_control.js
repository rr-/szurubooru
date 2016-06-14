'use strict';

const misc = require('../util/misc.js');
const views = require('../util/views.js');

class CommentFormControl {
    constructor(hostNode, comment, settings) {
        this._hostNode = hostNode;
        this._comment = comment || {text: ''};
        this._template = views.getTemplate('comment-form');
        this._settings = settings;
        this.install();
    }

    install() {
        const sourceNode = this._template({
            comment: this._comment,
        });

        const previewTabButton = sourceNode.querySelector('.buttons .preview');
        const editTabButton = sourceNode.querySelector('.buttons .edit');
        const formNode = sourceNode.querySelector('form');
        const cancelButton = sourceNode.querySelector('.cancel');
        const textareaNode = sourceNode.querySelector('form textarea');

        previewTabButton.addEventListener(
            'click', e => this._evtPreviewClick(e));
        editTabButton.addEventListener(
            'click', e => this._evtEditClick(e));

        formNode.addEventListener('submit', e => this._evtSaveClick(e));

        if (this._settings.canCancel) {
            cancelButton
                .addEventListener('click', e => this._evtCancelClick(e));
        } else {
            cancelButton.style.display = 'none';
        }

        for (let event of ['cut', 'paste', 'drop', 'keydown']) {
            textareaNode.addEventListener(event, e => {
                window.setTimeout(() => this._growTextArea(), 0);
            });
        }
        textareaNode.addEventListener('change', e => {
            misc.enableExitConfirmation();
            this._growTextArea();
        });

        views.replaceContent(this._hostNode, sourceNode);
    }

    enterEditMode() {
        this._freezeTabHeights();
        this._hostNode.classList.add('editing');
        this._selectTab('edit');
        this._growTextArea();
    }

    exitEditMode() {
        this._hostNode.classList.remove('editing');
        this._hostNode.querySelector('.tabs-wrapper').style.minHeight = null;
        misc.disableExitConfirmation();
        views.clearMessages(this._hostNode);
        this.setText(this._comment.text);
    }

    get _textareaNode() {
        return this._hostNode.querySelector('.edit.tab textarea');
    }

    get _contentNode() {
        return this._hostNode.querySelector('.preview.tab .comment-content');
    }

    setText(text) {
        this._textareaNode.value = text;
        this._contentNode.innerHTML = misc.formatMarkdown(text);
    }

    showError(message) {
        views.showError(this._hostNode, message);
    }

    _evtPreviewClick(e) {
        e.preventDefault();
        this._contentNode.innerHTML =
            misc.formatMarkdown(this._textareaNode.value);
        this._freezeTabHeights();
        this._selectTab('preview');
    }

    _evtEditClick(e) {
        e.preventDefault();
        this.enterEditMode();
    }

    _evtSaveClick(e) {
        e.preventDefault();
        if (!this._settings.onSave) {
            throw 'No save handler';
        }
        this._settings.onSave(this._textareaNode.value)
            .then(() => { misc.disableExitConfirmation(); });
    }

    _evtCancelClick(e) {
        e.preventDefault();
        this.exitEditMode();
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
        this._textareaNode.style.height =
            Math.max(
                this._settings.minHeight || 0,
                this._textareaNode.scrollHeight) + 'px';
    }
};

module.exports = CommentFormControl;
