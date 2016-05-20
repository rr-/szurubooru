'use strict';

const views = require('../util/views.js');

class FileDropperControl {
    constructor(target, options) {
        this._options = options;
        this._template = views.getTemplate('file-dropper');
        const source = this._template({
            allowMultiple: this._options.allowMultiple,
            id: 'file-' + Math.random().toString(36).substring(7),
        });

        this._dropperNode = source.querySelector('.file-dropper');
        this._fileInputNode = source.querySelector('input');
        this._fileInputNode.style.display = 'none';
        this._fileInputNode.multiple = this._options._allowMultiple || false;

        this._counter = 0;
        this._dropperNode.addEventListener(
            'dragenter', e => this._evtDragEnter(e));
        this._dropperNode.addEventListener(
            'dragleave', e => this._evtDragLeave(e));
        this._dropperNode.addEventListener(
            'dragover', e => this._evtDragOver(e));
        this._dropperNode.addEventListener(
            'drop', e => this._evtDrop(e));
        this._fileInputNode.addEventListener(
            'change', e => this._evtFileChange(e));

        views.showView(target, source);
    }

    _resolve(files) {
        files = Array.from(files);
        if (this._options.lock) {
            this._dropperNode.innerText =
                files.map(file => file.name).join(', ');
        }
        this._options.resolve(files);
    };

    _evtFileChange(e) {
        this._resolve(e.target.files);
    }

    _evtDragEnter(e) {
        this._dropperNode.classList.add('active');
        counter++;
    }

    _evtDragLeave(e) {
        this._counter--;
        if (this._counter === 0) {
            this._dropperNode.classList.remove('active');
        }
    }

    _evtDragOver(e) {
        e.preventDefault();
    }

    _evtDrop(e) {
        e.preventDefault();
        this._dropperNode.classList.remove('active');
        if (!e.dataTransfer.files.length) {
            window.alert('Only files are supported.');
        }
        if (!this._options.allowMultiple && e.dataTransfer.files.length > 1) {
            window.alert('Cannot select multiple files.');
        }
        this._resolve(e.dataTransfer.files);
    }
}

module.exports = FileDropperControl;
