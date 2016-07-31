'use strict';

const events = require('../events.js');
const views = require('../util/views.js');

const template = views.getTemplate('settings');

class SettingsView extends events.EventTarget {
    constructor(ctx) {
        super();

        this._hostNode = document.getElementById('content-holder');
        views.replaceContent(
            this._hostNode, template({browsingSettings: ctx.settings}));
        views.syncScrollPosition();

        views.decorateValidator(this._formNode);
        this._formNode.addEventListener('submit', e => this._evtSubmit(e));
    }

    clearMessages() {
        views.clearMessages(this._hostNode);
    }

    showSuccess(text) {
        views.showSuccess(this._hostNode, text);
    }

    _evtSubmit(e) {
        e.preventDefault();
        this.dispatchEvent(new CustomEvent('change', {
            detail: {
                settings: {
                    upscaleSmallPosts: this._formNode.querySelector(
                        '#upscale-small-posts').checked,
                    endlessScroll: this._formNode.querySelector(
                        '#endless-scroll').checked,
                    keyboardShortcuts: this._formNode.querySelector(
                        '#keyboard-shortcuts').checked,
                    transparencyGrid: this._formNode.querySelector(
                        '#transparency-grid').checked,
                    tagSuggestions: this._formNode.querySelector(
                        '#tag-suggestions').checked,
                },
            },
        }));
    }

    get _formNode() {
        return this._hostNode.querySelector('form');
    }
}

module.exports = SettingsView;
