"use strict";

const events = require("../events.js");
const views = require("../util/views.js");

const template = views.getTemplate("settings");

class SettingsView extends events.EventTarget {
    constructor(ctx) {
        super();

        this._hostNode = document.getElementById("content-holder");
        views.replaceContent(
            this._hostNode,
            template({ browsingSettings: ctx.settings })
        );
        views.syncScrollPosition();

        views.decorateValidator(this._formNode);
        this._formNode.addEventListener("submit", (e) => this._evtSubmit(e));

        const clearHistoryBtn = this._hostNode.querySelector("#clear-search-history");
        if (clearHistoryBtn) {
            clearHistoryBtn.addEventListener("click", (e) => {
                e.preventDefault();
                if (confirm("Are you sure you want to clear your search history?")) {
                    this.dispatchEvent(new CustomEvent("clearHistory"));
                }
            });
        }
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
                    searchHistoryEnabled: this._find("search-history-enabled").checked,
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
