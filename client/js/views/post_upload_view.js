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

function _listen(rootNode, selector, eventType, handler) {
    for (let node of rootNode.querySelectorAll(selector)) {
        node.addEventListener(eventType, e => handler(e));
    }
}

class Uploadable extends events.EventTarget {
    constructor() {
        super();
        this.safety = 'safe';
        this.flags = ['loop'];
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

    showError(message) {
        views.showError(this._hostNode, message);
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
            this._createRowNode(uploadable);
            uploadable.addEventListener(
                'finish', e => this._updateRowNode(e.detail.uploadable));
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
        }
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
        this._emit('submit');
    }

    _evtRemoveClick(e, uploadable) {
        e.preventDefault();
        if (this._uploading) {
            return;
        }
        this.removeUploadable(uploadable);
    }

    _evtMoveUpClick(e, uploadable) {
        e.preventDefault();
        if (this._uploading) {
            return;
        }
        let sortedUploadables = this._getSortedUploadables();
        if (uploadable.order > 0) {
            uploadable.order--;
            const prevUploadable = sortedUploadables[uploadable.order];
            prevUploadable.order++;
            uploadable.rowNode.parentNode.insertBefore(
                uploadable.rowNode, prevUploadable.rowNode);
        }
    }

    _evtMoveDownClick(e, uploadable) {
        e.preventDefault();
        if (this._uploading) {
            return;
        }
        let sortedUploadables = this._getSortedUploadables();
        if (uploadable.order + 1 < sortedUploadables.length) {
            uploadable.order++;
            const nextUploadable = sortedUploadables[uploadable.order];
            nextUploadable.order--;
            uploadable.rowNode.parentNode.insertBefore(
                nextUploadable.rowNode, uploadable.rowNode);
        }
    }

    _evtSafetyRadioboxChange(e, uploadable) {
        uploadable.safety = e.target.value;
    }

    _evtAnonymityCheckboxChange(e, uploadable) {
        uploadable.anonymous = e.target.checked;
    }

    _evtLoopVideoCheckboxChange(e, uploadable) {
        uploadable.flags = uploadable.flags.filter(f => f !== 'loop');
        if (e.target.checked) {
            uploadable.flags.push('loop');
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

    _createRowNode(uploadable) {
        const rowNode = rowTemplate(Object.assign(
            {}, this._ctx, {uploadable: uploadable}));
        this._listNode.appendChild(rowNode);

        _listen(rowNode, '.safety input', 'change',
            e => this._evtSafetyRadioboxChange(e, uploadable));
        _listen(rowNode, '.anonymous input', 'change',
            e => this._evtAnonymityCheckboxChange(e, uploadable));
        _listen(rowNode, '.loop-video input', 'change',
            e => this._evtLoopVideoCheckboxChange(e, uploadable));

        _listen(rowNode, 'a.remove', 'click',
            e => this._evtRemoveClick(e, uploadable));
        _listen(rowNode, 'a.move-up', 'click',
            e => this._evtMoveUpClick(e, uploadable));
        _listen(rowNode, 'a.move-down', 'click',
            e => this._evtMoveDownClick(e, uploadable));
        uploadable.rowNode = rowNode;
    }

    _updateRowNode(uploadable) {
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
