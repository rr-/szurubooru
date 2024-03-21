"use strict";

const settings = require("../models/settings.js");
const views = require("../util/views.js");
const optimizedResize = require("../util/optimized_resize.js");

class PostContentControl {
    constructor(hostNode, post, viewportSizeCalculator, overflowNode, fitFunctionOverride) {
        this._post = post;
        this._viewportSizeCalculator = viewportSizeCalculator;
        this._hostNode = hostNode;
        this._template = views.getTemplate("post-content");
        this._overflowNode = overflowNode;

        let fitMode = settings.get().fitMode;
        if (typeof fitFunctionOverride !== "undefined") {
            fitMode = fitFunctionOverride;
        }

        this._currentFitFunction =
            {
                "fit-both": this.fitBoth,
                "fit-original": this.fitOriginal,
                "fit-width": this.fitWidth,
                "fit-height": this.fitHeight,
            }[fitMode] || this.fitBoth;

        this._install();

        this._post.addEventListener("changeContent", (e) =>
            this._evtPostContentChange(e)
        );
    }

    disableOverlay() {
        this._hostNode.querySelector(".post-overlay").style.display = "none";
    }

    fitWidth() {
        if (this._overflowNode) this._overflowNode.style.overflowX = "hidden";
        this._currentFitFunction = this.fitWidth;
        const mul = this._post.canvasHeight / this._post.canvasWidth;
        let width = this._viewportWidth;
        if (!settings.get().upscaleSmallPosts) {
            width = Math.min(this._post.canvasWidth, width);
        }
        this._resize(width, width * mul);
    }

    fitHeight() {
        if (this._overflowNode) this._overflowNode.style.overflowX = "visible";
        this._currentFitFunction = this.fitHeight;
        const mul = this._post.canvasWidth / this._post.canvasHeight;
        let height = this._viewportHeight;
        if (!settings.get().upscaleSmallPosts) {
            height = Math.min(this._post.canvasHeight, height);
        }
        this._resize(height * mul, height);
    }

    fitBoth() {
        if (this._overflowNode) this._overflowNode.style.overflowX = "hidden";
        this._currentFitFunction = this.fitBoth;
        let mul = this._post.canvasHeight / this._post.canvasWidth;
        let width = this._viewportWidth;
        let height = this._viewportHeight;
        if (width * mul < height) {
            if (!settings.get().upscaleSmallPosts) {
                width = Math.min(this._post.canvasWidth, width);
            }
            this._resize(width, width * mul);
        } else {
            if (!settings.get().upscaleSmallPosts) {
                height = Math.min(this._post.canvasHeight, height);
            }
            this._resize(height / mul, height);
        }
    }

    fitOriginal() {
        if (this._overflowNode) this._overflowNode.style.overflowX = "visible";
        this._currentFitFunction = this.fitOriginal;
        this._resize(this._post.canvasWidth, this._post.canvasHeight);
    }

    get _viewportWidth() {
        return this._viewportSizeCalculator()[0];
    }

    get _viewportHeight() {
        return this._viewportSizeCalculator()[1];
    }

    _evtPostContentChange(e) {
        this._post = e.detail.post;
        this._post.mutateContentUrl();
        this._reinstall();
    }

    _resize(width, height) {
        const resizeListenerNodes = [this._postContentNode].concat(
            ...this._postContentNode.querySelectorAll(".resize-listener")
        );
        for (let node of resizeListenerNodes) {
            node.style.width = width + "px";
            node.style.height = height + "px";
        }
    }

    _refreshSize() {
        this._currentFitFunction();
    }

    _install() {
        this._reinstall();
        optimizedResize.add(() => this._refreshSize());
        views.monitorNodeRemoval(this._hostNode, () => {
            this._uninstall();
        });
    }

    _reinstall() {
        const newNode = this._template({
            post: this._post,
            autoplay: settings.get().autoplayVideos,
        });
        if (settings.get().transparencyGrid) {
            newNode.classList.add("transparency-grid");
        }
        if (this._postContentNode) {
            this._hostNode.replaceChild(newNode, this._postContentNode);
        } else {
            this._hostNode.appendChild(newNode);
        }
        this._postContentNode = newNode;
        this._refreshSize();
    }

    _uninstall() {
        optimizedResize.remove(() => this._refreshSize());
    }
}

module.exports = PostContentControl;
