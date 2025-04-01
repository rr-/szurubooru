"use strict";

const api = require("../api.js");
const events = require("../events.js");
const misc = require("../util/misc.js");
const views = require("../util/views.js");
const Note = require("../models/note.js");
const Point = require("../models/point.js");
const TagInputControl = require("./tag_input_control.js");
const PoolInputControl = require("./pool_input_control.js");
const ExpanderControl = require("../controls/expander_control.js");
const FileDropperControl = require("../controls/file_dropper_control.js");

const template = views.getTemplate("post-edit-sidebar");

class PostEditSidebarControl extends events.EventTarget {
    constructor(hostNode, post, postContentControl, postNotesOverlayControl) {
        super();
        this._hostNode = hostNode;
        this._post = post;
        this._postContentControl = postContentControl;
        this._postNotesOverlayControl = postNotesOverlayControl;
        this._newPostContent = null;

        this._postNotesOverlayControl.switchToPassiveEdit();

        views.replaceContent(
            this._hostNode,
            template({
                post: this._post,
                enableSafety: api.safetyEnabled(),
                hasClipboard: document.queryCommandSupported("copy"),
                canEditPostSafety: api.hasPrivilege("posts:edit:safety"),
                canEditPostSource: api.hasPrivilege("posts:edit:source"),
                canEditPostTags: api.hasPrivilege("posts:edit:tags"),
                canEditPostRelations: api.hasPrivilege("posts:edit:relations"),
                canEditPostNotes:
                    api.hasPrivilege("posts:edit:notes") &&
                    post.type !== "video" &&
                    post.type !== "flash",
                canEditPostFlags: api.hasPrivilege("posts:edit:flags"),
                canEditPostContent: api.hasPrivilege("posts:edit:content"),
                canEditPostThumbnail: api.hasPrivilege("posts:edit:thumbnail"),
                canEditPoolPosts: api.hasPrivilege("pools:edit:posts"),
                canCreateAnonymousPosts: api.hasPrivilege(
                    "posts:create:anonymous"
                ),
                canDeletePosts: api.hasPrivilege("posts:delete"),
                canFeaturePosts: api.hasPrivilege("posts:feature"),
                canMergePosts: api.hasPrivilege("posts:merge"),
            })
        );

        new ExpanderControl(
            "post-info",
            "Basic info",
            this._hostNode.querySelectorAll(
                ".safety, .relations, .flags, .post-source"
            )
        );
        this._tagsExpander = new ExpanderControl(
            "post-tags",
            `Tags (${this._post.tags.length})`,
            this._hostNode.querySelectorAll(".tags")
        );
        this._notesExpander = new ExpanderControl(
            "post-notes",
            "Notes",
            this._hostNode.querySelectorAll(".notes")
        );
        this._poolsExpander = new ExpanderControl(
            "post-pools",
            `Pools (${this._post.pools.length})`,
            this._hostNode.querySelectorAll(".pools")
        );
        new ExpanderControl(
            "post-content",
            "Content",
            this._hostNode.querySelectorAll(".post-content, .post-thumbnail")
        );
        new ExpanderControl(
            "post-management",
            "Management",
            this._hostNode.querySelectorAll(".management")
        );

        this._syncExpanderTitles();

        if (this._formNode) {
            this._formNode.addEventListener("submit", (e) =>
                this._evtSubmit(e)
            );
        }

        if (this._tagInputNode) {
            this._tagControl = new TagInputControl(
                this._tagInputNode,
                post.tags
            );
        }

        if (this._poolInputNode) {
            this._poolControl = new PoolInputControl(
                this._poolInputNode,
                post.pools
            );
        }

        if (this._contentInputNode) {
            this._contentFileDropper = new FileDropperControl(
                this._contentInputNode,
                {
                    allowUrls: true,
                    lock: true,
                    urlPlaceholder: "...or paste an URL here.",
                }
            );
            this._contentFileDropper.addEventListener("fileadd", (e) => {
                this._newPostContent = e.detail.files[0];
            });
            this._contentFileDropper.addEventListener("urladd", (e) => {
                this._newPostContent = e.detail.urls[0];
            });
        }

        if (this._thumbnailInputNode) {
            this._thumbnailFileDropper = new FileDropperControl(
                this._thumbnailInputNode,
                { lock: true }
            );
            this._thumbnailFileDropper.addEventListener("fileadd", (e) => {
                this._newPostThumbnail = e.detail.files[0];
                this._thumbnailRemovalLinkNode.style.display = "block";
            });
        }

        if (this._thumbnailRemovalLinkNode) {
            this._thumbnailRemovalLinkNode.addEventListener("click", (e) =>
                this._evtRemoveThumbnailClick(e)
            );
            this._thumbnailRemovalLinkUpdate(this._post);
        }

        if (this._addNoteLinkNode) {
            this._addNoteLinkNode.addEventListener("click", (e) =>
                this._evtAddNoteClick(e)
            );
        }

        if (this._copyNotesLinkNode) {
            this._copyNotesLinkNode.addEventListener("click", (e) =>
                this._evtCopyNotesClick(e)
            );
        }

        if (this._pasteNotesLinkNode) {
            this._pasteNotesLinkNode.addEventListener("click", (e) =>
                this._evtPasteNotesClick(e)
            );
        }

        if (this._deleteNoteLinkNode) {
            this._deleteNoteLinkNode.addEventListener("click", (e) =>
                this._evtDeleteNoteClick(e)
            );
        }

        if (this._featureLinkNode) {
            this._featureLinkNode.addEventListener("click", (e) =>
                this._evtFeatureClick(e)
            );
        }

        if (this._mergeLinkNode) {
            this._mergeLinkNode.addEventListener("click", (e) =>
                this._evtMergeClick(e)
            );
        }

        if (this._deleteLinkNode) {
            this._deleteLinkNode.addEventListener("click", (e) =>
                this._evtDeleteClick(e)
            );
        }

        this._postNotesOverlayControl.addEventListener("blur", (e) =>
            this._evtNoteBlur(e)
        );

        this._postNotesOverlayControl.addEventListener("focus", (e) =>
            this._evtNoteFocus(e)
        );

        this._post.addEventListener("changeContent", (e) =>
            this._evtPostContentChange(e)
        );

        this._post.addEventListener("changeThumbnail", (e) =>
            this._evtPostThumbnailChange(e)
        );

        if (this._formNode) {
            const inputNodes =
                this._formNode.querySelectorAll("input, textarea");
            for (let node of inputNodes) {
                node.addEventListener("change", (e) =>
                    this.dispatchEvent(new CustomEvent("change"))
                );
            }
            this._postNotesOverlayControl.addEventListener("change", (e) =>
                this.dispatchEvent(new CustomEvent("change"))
            );
        }

        for (let eventType of ["add", "remove"]) {
            this._post.notes.addEventListener(eventType, (e) => {
                this._syncExpanderTitles();
            });
            this._post.pools.addEventListener(eventType, (e) => {
                this._syncExpanderTitles();
            });
        }

        this._tagControl.addEventListener("change", (e) => {
            this.dispatchEvent(new CustomEvent("change"));
            this._syncExpanderTitles();
        });

        if (this._noteTextareaNode) {
            this._noteTextareaNode.addEventListener("change", (e) =>
                this._evtNoteTextChangeRequest(e)
            );
        }

        if (this._poolControl) {
            this._poolControl.addEventListener("change", (e) => {
                this.dispatchEvent(new CustomEvent("change"));
                this._syncExpanderTitles();
            });
        }
    }

    _syncExpanderTitles() {
        this._notesExpander.title = `Notes (${this._post.notes.length})`;
        this._tagsExpander.title = `Tags (${this._post.tags.length})`;
        this._poolsExpander.title = `Pools (${this._post.pools.length})`;
    }

    _thumbnailRemovalLinkUpdate(post) {
        if (this._thumbnailRemovalLinkNode) {
            this._thumbnailRemovalLinkNode.style.display = post
                .customThumbnailUrl
                ? "block"
                : "none";
        }
    }

    _evtPostContentChange(e) {
        this._contentFileDropper.reset();
        this._thumbnailRemovalLinkUpdate(e.detail.post);
        this._newPostContent = null;
    }

    _evtPostThumbnailChange(e) {
        this._thumbnailFileDropper.reset();
        this._thumbnailRemovalLinkUpdate(e.detail.post);
        this._newPostThumbnail = undefined;
    }

    _evtRemoveThumbnailClick(e) {
        e.preventDefault();
        this._thumbnailFileDropper.reset();
        this._newPostThumbnail = null;
        this._thumbnailRemovalLinkNode.style.display = "none";
    }

    _evtFeatureClick(e) {
        e.preventDefault();
        if (confirm("Are you sure you want to feature this post?")) {
            this.dispatchEvent(
                new CustomEvent("feature", {
                    detail: {
                        post: this._post,
                    },
                })
            );
        }
    }

    _evtMergeClick(e) {
        e.preventDefault();
        this.dispatchEvent(
            new CustomEvent("merge", {
                detail: {
                    post: this._post,
                },
            })
        );
    }

    _evtDeleteClick(e) {
        e.preventDefault();
        if (confirm("Are you sure you want to delete this post?")) {
            this.dispatchEvent(
                new CustomEvent("delete", {
                    detail: {
                        post: this._post,
                    },
                })
            );
        }
    }

    _evtNoteTextChangeRequest(e) {
        if (this._editedNote) {
            this._editedNote.text = this._noteTextareaNode.value;
        }
    }

    _evtNoteFocus(e) {
        this._editedNote = e.detail.note;
        this._addNoteLinkNode.classList.remove("inactive");
        this._deleteNoteLinkNode.classList.remove("inactive");
        this._noteTextareaNode.removeAttribute("disabled");
        this._noteTextareaNode.value = e.detail.note.text;
    }

    _evtNoteBlur(e) {
        this._evtNoteTextChangeRequest(null);
        this._addNoteLinkNode.classList.remove("inactive");
        this._deleteNoteLinkNode.classList.add("inactive");
        this._noteTextareaNode.blur();
        this._noteTextareaNode.setAttribute("disabled", "disabled");
        this._noteTextareaNode.value = "";
    }

    _evtAddNoteClick(e) {
        e.preventDefault();
        if (e.target.classList.contains("inactive")) {
            return;
        }
        this._addNoteLinkNode.classList.add("inactive");
        this._postNotesOverlayControl.switchToDrawing();
    }

    _evtCopyNotesClick(e) {
        e.preventDefault();
        let textarea = document.createElement("textarea");
        textarea.style.position = "fixed";
        textarea.style.opacity = "0";
        textarea.value = JSON.stringify(
            [...this._post.notes].map((note) => ({
                polygon: [...note.polygon].map((point) => [point.x, point.y]),
                text: note.text,
            }))
        );
        document.body.appendChild(textarea);
        textarea.select();

        let success = false;
        try {
            success = document.execCommand("copy");
        } catch (err) {
            // continue regardless of error
        }
        textarea.blur();
        document.body.removeChild(textarea);
        alert(
            success
                ? "Notes copied to clipboard."
                : "Failed to copy the text to clipboard. Sorry."
        );
    }

    _evtPasteNotesClick(e) {
        e.preventDefault();
        const text = window.prompt(
            "Please enter the exported notes snapshot:"
        );
        if (!text) {
            return;
        }
        const notesObj = JSON.parse(text);
        this._post.notes.clear();
        for (let noteObj of notesObj) {
            let note = new Note();
            for (let pointObj of noteObj.polygon) {
                note.polygon.add(new Point(pointObj[0], pointObj[1]));
            }
            note.text = noteObj.text;
            this._post.notes.add(note);
        }
    }

    _evtDeleteNoteClick(e) {
        e.preventDefault();
        if (e.target.classList.contains("inactive")) {
            return;
        }
        this._post.notes.remove(this._editedNote);
        this._postNotesOverlayControl.switchToPassiveEdit();
    }

    _evtSubmit(e) {
        e.preventDefault();
        this.dispatchEvent(
            new CustomEvent("submit", {
                detail: {
                    post: this._post,

                    safety: this._safetyButtonNodes.length
                        ? Array.from(this._safetyButtonNodes)
                              .filter((node) => node.checked)[0]
                              .value.toLowerCase()
                        : undefined,

                    flags: this._videoFlags,

                    tags: this._tagInputNode
                        ? misc.splitByWhitespace(this._tagInputNode.value)
                        : undefined,

                    pools: this._poolInputNode
                        ? misc.splitByWhitespace(this._poolInputNode.value)
                        : undefined,

                    relations: this._relationsInputNode
                        ? misc
                              .splitByWhitespace(
                                  this._relationsInputNode.value
                              )
                              .map((x) => parseInt(x))
                        : undefined,

                    content: this._newPostContent
                        ? this._newPostContent
                        : undefined,

                    thumbnail:
                        this._newPostThumbnail,

                    source: this._sourceInputNode
                        ? this._sourceInputNode.value
                        : undefined,
                },
            })
        );
    }

    get _formNode() {
        return this._hostNode.querySelector("form");
    }

    get _submitButtonNode() {
        return this._hostNode.querySelector(".submit");
    }

    get _safetyButtonNodes() {
        return this._formNode.querySelectorAll(".safety input");
    }

    get _tagInputNode() {
        return this._formNode.querySelector(".tags input");
    }

    get _poolInputNode() {
        return this._formNode.querySelector(".pools input");
    }

    get _loopVideoInputNode() {
        return this._formNode.querySelector(".flags input[name=loop]");
    }

    get _soundVideoInputNode() {
        return this._formNode.querySelector(".flags input[name=sound]");
    }

    get _videoFlags() {
        if (!this._loopVideoInputNode) {
            return undefined;
        }
        let ret = [];
        if (this._loopVideoInputNode.checked) {
            ret.push("loop");
        }
        if (this._soundVideoInputNode.checked) {
            ret.push("sound");
        }
        return ret;
    }

    get _relationsInputNode() {
        return this._formNode.querySelector(".relations input");
    }

    get _contentInputNode() {
        return this._formNode.querySelector(
            ".post-content .dropper-container"
        );
    }

    get _thumbnailInputNode() {
        return this._formNode.querySelector(
            ".post-thumbnail .dropper-container"
        );
    }

    get _thumbnailRemovalLinkNode() {
        return this._formNode.querySelector(".post-thumbnail a");
    }

    get _sourceInputNode() {
        return this._formNode.querySelector(".post-source textarea");
    }

    get _featureLinkNode() {
        return this._formNode.querySelector(".management .feature");
    }

    get _mergeLinkNode() {
        return this._formNode.querySelector(".management .merge");
    }

    get _deleteLinkNode() {
        return this._formNode.querySelector(".management .delete");
    }

    get _addNoteLinkNode() {
        return this._formNode.querySelector(".notes .add");
    }

    get _copyNotesLinkNode() {
        return this._formNode.querySelector(".notes .copy");
    }

    get _pasteNotesLinkNode() {
        return this._formNode.querySelector(".notes .paste");
    }

    get _deleteNoteLinkNode() {
        return this._formNode.querySelector(".notes .delete");
    }

    get _noteTextareaNode() {
        return this._formNode.querySelector(".notes textarea");
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
}

module.exports = PostEditSidebarControl;
