'use strict';

const events = require('../events.js');
const views = require('../util/views.js');
const FileDropperControl = require('../controls/file_dropper_control.js');

const template = views.getTemplate('post-upload');
const rowTemplate = views.getTemplate('post-upload-row');

let globalOrder = 0;

function _mimeTypeToPostType(mimeType) {
    return {
        'application/x-shockwave-flash': 'flash',
        'image/gif': 'image',
        'image/jpeg': 'image',
        'image/png': 'image',
        'video/mp4': 'video',
        'video/webm': 'video',
    }[mimeType] || 'unknown';
}

class Uploadable extends events.EventTarget {
    constructor() {
        super();
        this.lookalikes = [];
        this.lookalikesConfirmed = false;
        this.safety = 'safe';
        this.flags = [];
        this.tags = [];
        this.relations = [];
        this.anonymous = false;
        this.order = globalOrder;
        globalOrder++;
    }

    destroy() {
    }

    get mimeType() {
        return 'application/octet-stream';
    }

    get type() {
        return _mimeTypeToPostType(this.mimeType);
    }

    get key() {
        throw new Error('Not implemented');
    }

    get name() {
        throw new Error('Not implemented');
    }

    _initComplete() {
        if (['video'].includes(this.type)) {
            this.flags.push('loop');
        }
    }
}

class File extends Uploadable {
    constructor(file) {
        super();
        this.file = file;

        this._previewUrl = null;
        if (URL && URL.createObjectURL) {
            this._previewUrl = URL.createObjectURL(file);
        } else {
            let reader = new FileReader();
            reader.readAsDataURL(file);
            reader.addEventListener('load', e => {
                this._previewUrl = e.target.result;
                this.dispatchEvent(
                    new CustomEvent('finish', {detail: {uploadable: this}}));
            });
        }
        this._initComplete();
    }

    destroy() {
        if (URL && URL.createObjectURL && URL.revokeObjectURL) {
            URL.revokeObjectURL(this._previewUrl);
        }
    }

    get mimeType() {
        return this.file.type;
    }

    get previewUrl() {
        return this._previewUrl;
    }

    get key() {
        return this.file.name + this.file.size;
    }

    get name() {
        return this.file.name;
    }
}

class Url extends Uploadable {
    constructor(url) {
        super();
        this.url = url;
        this.dispatchEvent(new CustomEvent('finish'));
        this._initComplete();
    }

    get mimeType() {
        let mime = {
            'swf': 'application/x-shockwave-flash',
            'jpg': 'image/jpeg',
            'png': 'image/png',
            'gif': 'image/gif',
            'mp4': 'video/mp4',
            'webm': 'video/webm',
        };
        for (let extension of Object.keys(mime)) {
            if (this.url.toLowerCase().indexOf('.' + extension) !== -1) {
                return mime[extension];
            }
        }
        return 'unknown';
    }

    get previewUrl() {
        return this.url;
    }

    get key() {
        return this.url;
    }

    get name() {
        return this.url;
    }
}

class PostUploadView extends events.EventTarget {
    constructor(ctx) {
        super();
        this._ctx = ctx;
        this._hostNode = document.getElementById('content-holder');

        views.replaceContent(this._hostNode, template());
        views.syncScrollPosition();

        this._cancelButtonNode.disabled = true;

        this._uploadables = new Map();
        this._contentFileDropper = new FileDropperControl(
            this._contentInputNode,
            {
                allowUrls: true,
                allowMultiple: true,
                lock: false,
            });
        this._contentFileDropper.addEventListener(
            'fileadd', e => this._evtFilesAdded(e));
        this._contentFileDropper.addEventListener(
            'urladd', e => this._evtUrlsAdded(e));

        this._cancelButtonNode.addEventListener(
            'click', e => this._evtCancelButtonClick(e));
        this._formNode.addEventListener('submit', e => this._evtFormSubmit(e));
        this._formNode.classList.add('inactive');
    }

    enableForm() {
        views.enableForm(this._formNode);
        this._cancelButtonNode.disabled = true;
        this._formNode.classList.remove('uploading');
    }

    disableForm() {
        views.disableForm(this._formNode);
        this._cancelButtonNode.disabled = false;
        this._formNode.classList.add('uploading');
    }

    clearMessages() {
        views.clearMessages(this._hostNode);
    }

    showSuccess(message) {
        views.showSuccess(this._hostNode, message);
    }

    showError(message, uploadable) {
        this._showMessage(views.showError, message, uploadable);
    }

    showInfo(message, uploadable) {
        this._showMessage(views.showInfo, message, uploadable);
    }

    _showMessage(functor, message, uploadable) {
        functor(uploadable ? uploadable.rowNode : this._hostNode, message);
    }

    addUploadables(uploadables) {
        this._formNode.classList.remove('inactive');
        let duplicatesFound = 0;
        for (let uploadable of uploadables) {
            if (this._uploadables.has(uploadable.key)) {
                duplicatesFound++;
                continue;
            }
            this._uploadables.set(uploadable.key, uploadable);
            this._emit('change');
            this._renderRowNode(uploadable);
            uploadable.addEventListener(
                'finish', e => this._updateThumbnailNode(e.detail.uploadable));
        }
        if (duplicatesFound) {
            let message = null;
            if (duplicatesFound < uploadables.length) {
                message = 'Some of the files were already added ' +
                    'and have been skipped.';
            } else if (duplicatesFound === 1) {
                message = 'This file was already added.';
            } else {
                message = 'These files were already added.';
            }
            alert(message);
        }
    }

    removeUploadable(uploadable) {
        if (!this._uploadables.has(uploadable.key)) {
            return;
        }
        uploadable.destroy();
        uploadable.rowNode.parentNode.removeChild(uploadable.rowNode);
        this._uploadables.delete(uploadable.key);
        this._normalizeUploadablesOrder();
        this._emit('change');
        if (!this._uploadables.size) {
            this._formNode.classList.add('inactive');
            this._submitButtonNode.value = 'Upload all';
        }
    }

    updateUploadable(uploadable) {
        uploadable.lookalikesConfirmed = true;
        this._renderRowNode(uploadable);
    }

    _evtFilesAdded(e) {
        this.addUploadables(e.detail.files.map(file => new File(file)));
    }

    _evtUrlsAdded(e) {
        this.addUploadables(e.detail.urls.map(url => new Url(url)));
    }

    _evtCancelButtonClick(e) {
        e.preventDefault();
        this._emit('cancel');
    }

    _evtFormSubmit(e) {
        e.preventDefault();
        for (let uploadable of this._uploadables.values()) {
            this._updateUploadableFromDom(uploadable);
        }
        this._submitButtonNode.value = 'Resume upload';
        this._emit('submit');
    }

    _updateUploadableFromDom(uploadable) {
        const rowNode = uploadable.rowNode;
        uploadable.safety =
            rowNode.querySelector('.safety input:checked').value;
        uploadable.anonymous =
            rowNode.querySelector('.anonymous input').checked;
        if (rowNode.querySelector('.loop-video input:checked')) {
            uploadable.flags.push('loop');
        }
        uploadable.tags = [];
        uploadable.relations = [];
        for (let [i, lookalike] of uploadable.lookalikes.entries()) {
            let lookalikeNode = rowNode.querySelector(
                `.lookalikes li:nth-child(${i + 1})`);
            if (lookalikeNode.querySelector('[name=copy-tags]').checked) {
                uploadable.tags = uploadable.tags.concat(lookalike.post.tags);
            }
            if (lookalikeNode.querySelector('[name=add-relation]').checked) {
                uploadable.relations.push(lookalike.post.id);
            }
        }
    }

    _evtRemoveClick(e, uploadable) {
        e.preventDefault();
        if (this._uploading) {
            return;
        }
        this.removeUploadable(uploadable);
    }

    _evtMoveClick(e, uploadable, delta) {
        e.preventDefault();
        if (this._uploading) {
            return;
        }
        let sortedUploadables = this._getSortedUploadables();
        if ((uploadable.order + delta).between(-1, sortedUploadables.length)) {
            uploadable.order += delta;
            const otherUploadable = sortedUploadables[uploadable.order];
            otherUploadable.order -= delta;
            if (delta === 1) {
                uploadable.rowNode.parentNode.insertBefore(
                    otherUploadable.rowNode, uploadable.rowNode);
            } else {
                uploadable.rowNode.parentNode.insertBefore(
                    uploadable.rowNode, otherUploadable.rowNode);
            }
        }
    }

    _normalizeUploadablesOrder() {
        let sortedUploadables = this._getSortedUploadables();
        for (let i = 0; i < sortedUploadables.length; i++) {
            sortedUploadables[i].order = i;
        }
    }

    _getSortedUploadables() {
        let sortedUploadables = [...this._uploadables.values()];
        sortedUploadables.sort((a, b) => a.order - b.order);
        return sortedUploadables;
    }

    _emit(eventType) {
        this.dispatchEvent(
            new CustomEvent(
                eventType,
                {detail: {
                    uploadables: this._getSortedUploadables(),
                    skipDuplicates: this._skipDuplicatesCheckboxNode.checked,
                }}));
    }

    _renderRowNode(uploadable) {
        const rowNode = rowTemplate(Object.assign(
            {}, this._ctx, {uploadable: uploadable}));
        if (uploadable.rowNode) {
            uploadable.rowNode.parentNode.replaceChild(
                rowNode, uploadable.rowNode);
        } else {
            this._listNode.appendChild(rowNode);
        }

        uploadable.rowNode = rowNode;

        rowNode.querySelector('a.remove').addEventListener('click',
            e => this._evtRemoveClick(e, uploadable));
        rowNode.querySelector('a.move-up').addEventListener('click',
            e => this._evtMoveClick(e, uploadable, -1));
        rowNode.querySelector('a.move-down').addEventListener('click',
            e => this._evtMoveClick(e, uploadable, 1));
    }

    _updateThumbnailNode(uploadable) {
        const rowNode = rowTemplate(Object.assign(
            {}, this._ctx, {uploadable: uploadable}));
        views.replaceContent(
            uploadable.rowNode.querySelector('.thumbnail'),
            rowNode.querySelector('.thumbnail').childNodes);
    }

    get _uploading() {
        return this._formNode.classList.contains('uploading');
    }

    get _listNode() {
        return this._hostNode.querySelector('.uploadables-container');
    }

    get _formNode() {
        return this._hostNode.querySelector('form');
    }

    get _skipDuplicatesCheckboxNode() {
        return this._hostNode.querySelector('form [name=skip-duplicates]');
    }

    get _submitButtonNode() {
        return this._hostNode.querySelector('form [type=submit]');
    }

    get _cancelButtonNode() {
        return this._hostNode.querySelector('form .cancel');
    }

    get _contentInputNode() {
        return this._formNode.querySelector('.dropper-container');
    }
}

module.exports = PostUploadView;
