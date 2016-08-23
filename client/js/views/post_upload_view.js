'use strict';

const events = require('../events.js');
const views = require('../util/views.js');
const FileDropperControl = require('../controls/file_dropper_control.js');

const template = views.getTemplate('post-upload');
const rowTemplate = views.getTemplate('post-upload-row');

let globalOrder = 0;

class Uploadable extends events.EventTarget {
    constructor() {
        super();
        this.safety = 'safe';
        this.anonymous = false;
        this.order = globalOrder;
        globalOrder++;
    }

    get type() {
        return 'unknown';
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

        this._imageUrl = null;
        let reader = new FileReader();
        reader.readAsDataURL(file);
        reader.addEventListener('load', e => {
            this._imageUrl = e.target.result;
            this.dispatchEvent(
                new CustomEvent('finish', {detail: {uploadable: this}}));
        });
    }

    get type() {
        return {
            'application/x-shockwave-flash': 'flash',
            'image/gif': 'image',
            'image/jpeg': 'image',
            'image/png': 'image',
            'video/mp4': 'video',
            'video/webm': 'video',
        }[this.file.type] || 'unknown';
    }

    get imageUrl() {
        return this._imageUrl;
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

    get type() {
        let extensions = {
            'swf': 'flash',
            'jpg': 'image',
            'png': 'image',
            'gif': 'image',
            'mp4': 'video',
            'webm': 'video',
        };
        for (let extension of Object.keys(extensions)) {
            if (this.url.toLowerCase().indexOf('.' + extension) !== -1) {
                return extensions[extension];
            }
        }
        return 'unknown';
    }

    get imageUrl() {
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

        this._formNode.addEventListener('submit', e => this._evtFormSubmit(e));
        this._formNode.classList.add('inactive');
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
        uploadable.rowNode.parentNode.removeChild(uploadable.rowNode);
        this._uploadables.delete(uploadable.key);
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

    _evtFormSubmit(e) {
        e.preventDefault();
        this._emit('submit');
    }

    _evtRemoveClick(e, uploadable) {
        e.preventDefault();
        this.removeUploadable(uploadable);
    }

    _evtSafetyRadioboxChange(e, uploadable) {
        uploadable.safety = e.target.value;
    }

    _evtAnonymityCheckboxChange(e, uploadable) {
        uploadable.anonymous = e.target.checked;
    }

    _emit(eventType) {
        let sortedUploadables = [...this._uploadables.values()];
        sortedUploadables.sort((a, b) => a.order - b.order);
        this.dispatchEvent(
            new CustomEvent(
                eventType, {detail: {uploadables: sortedUploadables}}));
    }

    _createRowNode(uploadable) {
        const rowNode = rowTemplate(Object.assign(
            {}, this._ctx, {uploadable: uploadable}));
        this._listNode.appendChild(rowNode);

        for (let radioboxNode of rowNode.querySelectorAll('.safety input')) {
            radioboxNode.addEventListener(
                'change', e => this._evtSafetyRadioboxChange(e, uploadable));
        }

        const anonymousCheckboxNode = rowNode.querySelector('.anonymous input');
        if (anonymousCheckboxNode) {
            anonymousCheckboxNode.addEventListener(
                'change', e => this._evtAnonymityCheckboxChange(e, uploadable));
        }

        rowNode.querySelector('a.remove').addEventListener(
            'click', e => this._evtRemoveClick(e, uploadable));
        uploadable.rowNode = rowNode;
    }

    _updateRowNode(uploadable) {
        const rowNode = rowTemplate(Object.assign(
            {}, this._ctx, {uploadable: uploadable}));
        views.replaceContent(
            uploadable.rowNode.querySelector('.thumbnail'),
            rowNode.querySelector('.thumbnail').childNodes);
    }

    get _listNode() {
        return this._hostNode.querySelector('.uploadables-container');
    }

    get _formNode() {
        return this._hostNode.querySelector('form');
    }

    get _submitButtonNode() {
        return this._hostNode.querySelector('form [type=submit]');
    }

    get _contentInputNode() {
        return this._formNode.querySelector('.dropper-container');
    }
}

module.exports = PostUploadView;
