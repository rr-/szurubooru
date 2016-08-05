'use strict';

const api = require('../api.js');
const events = require('../events.js');
const misc = require('../util/misc.js');
const views = require('../util/views.js');
const TagInputControl = require('./tag_input_control.js');
const ExpanderControl = require('../controls/expander_control.js');
const FileDropperControl = require('../controls/file_dropper_control.js');

const template = views.getTemplate('post-edit-sidebar');

class PostEditSidebarControl extends events.EventTarget {
    constructor(hostNode, post, postContentControl, postNotesOverlayControl) {
        super();
        this._hostNode = hostNode;
        this._post = post;
        this._postContentControl = postContentControl;
        this._postNotesOverlayControl = postNotesOverlayControl;
        this._newPostContent = null;

        this._postNotesOverlayControl.switchToPassiveEdit();

        views.replaceContent(this._hostNode, template({
            post: this._post,
            canEditPostSafety: api.hasPrivilege('posts:edit:safety'),
            canEditPostSource: api.hasPrivilege('posts:edit:source'),
            canEditPostTags: api.hasPrivilege('posts:edit:tags'),
            canEditPostRelations: api.hasPrivilege('posts:edit:relations'),
            canEditPostNotes: api.hasPrivilege('posts:edit:notes'),
            canEditPostFlags: api.hasPrivilege('posts:edit:flags'),
            canEditPostContent: api.hasPrivilege('posts:edit:content'),
            canEditPostThumbnail: api.hasPrivilege('posts:edit:thumbnail'),
            canCreateAnonymousPosts: api.hasPrivilege('posts:create:anonymous'),
            canDeletePosts: api.hasPrivilege('posts:delete'),
            canFeaturePosts: api.hasPrivilege('posts:feature'),
        }));

        new ExpanderControl(
            'Basic info',
            this._hostNode.querySelectorAll('.safety, .relations, .flags'));
        new ExpanderControl(
            'Tags',
            this._hostNode.querySelectorAll('.tags'));
        new ExpanderControl(
            'Notes',
            this._hostNode.querySelectorAll('.notes'));
        new ExpanderControl(
            'Content',
            this._hostNode.querySelectorAll('.post-content, .post-thumbnail'));
        new ExpanderControl(
            'Management',
            this._hostNode.querySelectorAll('.management'));

        if (this._formNode) {
            this._formNode.addEventListener('submit', e => this._evtSubmit(e));
        }

        if (this._tagInputNode) {
            this._tagControl = new TagInputControl(this._tagInputNode);
        }

        if (this._contentInputNode) {
            this._contentFileDropper = new FileDropperControl(
                this._contentInputNode,
                {
                    lock: true,
                    resolve: files => {
                        this._newPostContent = files[0];
                    },
                });
        }

        if (this._thumbnailInputNode) {
            this._thumbnailFileDropper = new FileDropperControl(
                this._thumbnailInputNode,
                {
                    lock: true,
                    resolve: files => {
                        this._newPostThumbnail = files[0];
                        this._thumbnailRemovalLinkNode.style.display = 'block';
                    },
                });
        }

        if (this._thumbnailRemovalLinkNode) {
            this._thumbnailRemovalLinkNode.addEventListener(
                'click', e => this._evtRemoveThumbnailClick(e));
            this._thumbnailRemovalLinkNode.style.display =
                this._post.hasCustomThumbnail ? 'block' : 'none';
        }

        if (this._addNoteLinkNode) {
            this._addNoteLinkNode.addEventListener(
                'click', e => this._evtAddNoteClick(e));
        }

        if (this._deleteNoteLinkNode) {
            this._deleteNoteLinkNode.addEventListener(
                'click', e => this._evtDeleteNoteClick(e));
        }

        if (this._featureLinkNode) {
            this._featureLinkNode.addEventListener(
                'click', e => this._evtFeatureClick(e));
        }

        if (this._deleteLinkNode) {
            this._deleteLinkNode.addEventListener(
                'click', e => this._evtDeleteClick(e));
        }

        this._postNotesOverlayControl.addEventListener(
            'blur', e => this._evtNoteBlur(e));

        this._postNotesOverlayControl.addEventListener(
            'focus', e => this._evtNoteFocus(e));

        this._post.addEventListener(
            'changeContent', e => this._evtPostContentChange(e));

        this._post.addEventListener(
            'changeThumbnail', e => this._evtPostThumbnailChange(e));

        if (this._formNode) {
            const inputNodes = this._formNode.querySelectorAll(
                'input, textarea');
            for (let node of inputNodes) {
                node.addEventListener(
                    'change', e => {
                        this.dispatchEvent(new CustomEvent('change'));
                    });
            }
            this._postNotesOverlayControl.addEventListener(
                'change', e => {
                    this.dispatchEvent(new CustomEvent('change'));
                });
        }

        if (this._noteTextareaNode) {
            this._noteTextareaNode.addEventListener(
                'change', e => this._evtNoteTextChangeRequest(e));
        }
    }

    _evtPostContentChange(e) {
        this._contentFileDropper.reset();
    }

    _evtPostThumbnailChange(e) {
        this._thumbnailFileDropper.reset();
    }

    _evtRemoveThumbnailClick(e) {
        this._thumbnailFileDropper.reset();
        this._newPostThumbnail = null;
        this._thumbnailRemovalLinkNode.style.display = 'none';
    }

    _evtFeatureClick(e) {
        if (confirm('Are you sure you want to feature this post?')) {
            this.dispatchEvent(new CustomEvent('feature', {
                detail: {
                    post: this._post,
                },
            }));
        }
    }

    _evtDeleteClick(e) {
        if (confirm('Are you sure you want to delete this post?')) {
            this.dispatchEvent(new CustomEvent('delete', {
                detail: {
                    post: this._post,
                },
            }));
        }
    }

    _evtNoteTextChangeRequest(e) {
        if (this._editedNote) {
            this._editedNote.text = this._noteTextareaNode.value;
        }
    }

    _evtNoteFocus(e) {
        this._editedNote = e.detail.note;
        this._addNoteLinkNode.classList.remove('inactive');
        this._deleteNoteLinkNode.classList.remove('inactive');
        this._noteTextareaNode.removeAttribute('disabled');
        this._noteTextareaNode.value = e.detail.note.text;
    }

    _evtNoteBlur(e) {
        this._evtNoteTextChangeRequest(null);
        this._addNoteLinkNode.classList.remove('inactive');
        this._deleteNoteLinkNode.classList.add('inactive');
        this._noteTextareaNode.blur();
        this._noteTextareaNode.setAttribute('disabled', 'disabled');
        this._noteTextareaNode.value = '';
    }

    _evtAddNoteClick(e) {
        if (e.target.classList.contains('inactive')) {
            return;
        }
        this._addNoteLinkNode.classList.add('inactive');
        this._postNotesOverlayControl.switchToDrawing();
    }

    _evtDeleteNoteClick(e) {
        if (e.target.classList.contains('inactive')) {
            return;
        }
        this._post.notes.remove(this._editedNote);
        this._postNotesOverlayControl.switchToPassiveEdit();
    }

    _evtSubmit(e) {
        e.preventDefault();
        this.dispatchEvent(new CustomEvent('submit', {
            detail: {
                post: this._post,

                safety: this._safetyButtonNodes.legnth ?
                    Array.from(this._safetyButtonNodes)
                        .filter(node => node.checked)[0]
                        .value.toLowerCase() :
                    undefined,

                flags: this._loopVideoInputNode ?
                    (this._loopVideoInputNode.checked ? ['loop'] : []) :
                    undefined,

                tags: this._tagInputNode ?
                    misc.splitByWhitespace(this._tagInputNode.value) :
                    undefined,

                relations: this._relationsInputNode ?
                    misc.splitByWhitespace(this._relationsInputNode.value) :
                    undefined,

                content: this._newPostContent ?
                    this._newPostContent :
                    undefined,

                thumbnail: this._newPostThumbnail !== undefined ?
                    this._newPostThumbnail :
                    undefined,
            },
        }));
    }

    get _formNode() {
        return this._hostNode.querySelector('form');
    }

    get _submitButtonNode() {
        return this._hostNode.querySelector('.submit');
    }

    get _safetyButtonNodes() {
        return this._formNode.querySelectorAll('.safety input');
    }

    get _tagInputNode() {
        return this._formNode.querySelector('.tags input');
    }

    get _loopVideoInputNode() {
        return this._formNode.querySelector('.flags input[name=loop]');
    }

    get _relationsInputNode() {
        return this._formNode.querySelector('.relations input');
    }

    get _contentInputNode() {
        return this._formNode.querySelector('.post-content .dropper-container');
    }

    get _thumbnailInputNode() {
        return this._formNode.querySelector(
            '.post-thumbnail .dropper-container');
    }

    get _thumbnailRemovalLinkNode() {
        return this._formNode.querySelector('.post-thumbnail a');
    }

    get _featureLinkNode() {
        return this._formNode.querySelector('.management .feature');
    }

    get _deleteLinkNode() {
        return this._formNode.querySelector('.management .delete');
    }

    get _addNoteLinkNode() {
        return this._formNode.querySelector('.notes .add');
    }

    get _deleteNoteLinkNode() {
        return this._formNode.querySelector('.notes .delete');
    }

    get _noteTextareaNode() {
        return this._formNode.querySelector('.notes textarea');
    }

    enableForm() {
        views.enableForm(this._formNode);
    }

    disableForm() {
        views.disableForm(this._formNode);
    }

    clearMessages() {
        views.clearMessages(this._hostNode);
    }

    showSuccess(message) {
        views.showSuccess(this._hostNode, message);
    }

    showError(message) {
        views.showError(this._hostNode, message);
    }
};

module.exports = PostEditSidebarControl;
