'use strict';

const api = require('../api.js');
const events = require('../events.js');
const misc = require('../util/misc.js');
const views = require('../util/views.js');
const TagInputControl = require('./tag_input_control.js');
const FileDropperControl = require('../controls/file_dropper_control.js');

const template = views.getTemplate('post-edit-sidebar');

class PostEditSidebarControl extends events.EventTarget {
    constructor(hostNode, post, postContentControl) {
        super();
        this._hostNode = hostNode;
        this._post = post;
        this._postContentControl = postContentControl;
        this._newPostContent = null;

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
                    },
                });
        }

        this._post.addEventListener(
            'changeContent', e => this._evtPostContentChange(e));

        this._post.addEventListener(
            'changeThumbnail', e => this._evtPostThumbnailChange(e));
    }

    _evtPostContentChange(e) {
        this._contentFileDropper.reset();
    }

    _evtPostThumbnailChange(e) {
        this._thumbnailFileDropper.reset();
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

                thumbnail: this._newPostThumbnail ?
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
};

module.exports = PostEditSidebarControl;
