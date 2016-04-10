'use strict';

const views = require('../util/views.js');

class FileDropperControl {
    constructor() {
        this.template = views.getTemplate('file-dropper');
    }

    render(ctx) {
        const target = ctx.target;
        const source = this.template({
            id: 'file-' + Math.random().toString(36).substring(7),
        });

        const dropper = source.querySelector('.file-dropper');
        const fileInput = source.querySelector('input');
        fileInput.style.display = 'none';
        fileInput.multiple = ctx.allowMultiple || false;

        const resolve = files => {
            files = Array.from(files);
            if (ctx.lock) {
                dropper.innerText = files.map(file => file.name).join(', ');
            }
            ctx.resolve(files);
        };

        let counter = 0;
        dropper.addEventListener('dragenter', e => {
            dropper.classList.add('active');
            counter++;
        });

        dropper.addEventListener('dragleave', e => {
            counter--;
            if (counter === 0) {
                dropper.classList.remove('active');
            }
        });

        dropper.addEventListener('dragover', e => {
            e.preventDefault();
        });

        dropper.addEventListener('drop', e => {
            dropper.classList.remove('active');
            e.preventDefault();
            if (!e.dataTransfer.files.length) {
                window.alert('Only files are supported.');
            }
            if (!ctx.allowMultiple && e.dataTransfer.files.length > 1) {
                window.alert('Cannot select multiple files.');
            }
            resolve(e.dataTransfer.files);
        });

        fileInput.addEventListener('change', e => {
            resolve(e.target.files);
        });

        views.showView(target, source);
    }
}

module.exports = FileDropperControl;
