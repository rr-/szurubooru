"use strict";

const events = require("../events.js");
const views = require("../util/views.js");

const template = views.getTemplate("settings");

// A geometric progression centered on the default, so that every step is an
// equally sized visual jump. Keys are formatted to a fixed number of decimals
// to stay out of the integer-index bucket, which would reorder the 1x entry
// to the front of the select.
const postSizes = {};
for (let scale of [0.63, 0.7, 0.79, 0.89, 1, 1.12, 1.26, 1.42, 1.6]) {
    postSizes[scale.toFixed(2)] = Math.round(scale * 100) + "%";
}

class SettingsView extends events.EventTarget {
    constructor(ctx) {
        super();

        this._hostNode = document.getElementById("content-holder");
        views.replaceContent(
            this._hostNode,
            template({ browsingSettings: ctx.settings, postSizes: postSizes })
        );
        views.syncScrollPosition();

        views.decorateValidator(this._formNode);
        this._formNode.addEventListener("submit", (e) => this._evtSubmit(e));
    }

    clearMessages() {
        views.clearMessages(this._hostNode);
    }

    showSuccess(text) {
        views.showSuccess(this._hostNode, text);
    }

    _evtSubmit(e) {
        e.preventDefault();
        this.dispatchEvent(
            new CustomEvent("submit", {
                detail: {
                    upscaleSmallPosts: this._find("upscale-small-posts")
                        .checked,
                    endlessScroll: this._find("endless-scroll").checked,
                    keyboardShortcuts: this._find("keyboard-shortcuts")
                        .checked,
                    transparencyGrid: this._find("transparency-grid").checked,
                    tagSuggestions: this._find("tag-suggestions").checked,
                    autoplayVideos: this._find("autoplay-videos").checked,
                    postsPerPage: this._find("posts-per-page").value,
                    tagUnderscoresAsSpaces: this._find("underscores-as-spaces")
                        .checked,
                    darkTheme: this._find("dark-theme").checked,
                    postFlow: this._find("post-flow").checked,
                    postSize: parseFloat(this._find("post-size").value),
                },
            })
        );
    }

    get _formNode() {
        return this._hostNode.querySelector("form");
    }

    _find(nodeName) {
        return this._formNode.querySelector("[name=" + nodeName + "]");
    }
}

module.exports = SettingsView;
