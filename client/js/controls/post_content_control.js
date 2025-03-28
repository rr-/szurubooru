"use strict";

const settings = require("../models/settings.js");
const views = require("../util/views.js");
const optimizedResize = require("../util/optimized_resize.js");

class PostContentControl {
    constructor(hostNode, post, viewportSizeCalculator, fitFunctionOverride) {
        this._post = post;
        this._viewportSizeCalculator = viewportSizeCalculator;
        this._hostNode = hostNode;
        this._template = views.getTemplate("post-content");

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
        this._currentFitFunction = this.fitWidth;
        const mul = this._post.canvasHeight / this._post.canvasWidth;
        let width = this._viewportWidth;
        if (!settings.get().upscaleSmallPosts) {
            width = Math.min(this._post.canvasWidth, width);
        }
        this._resize(width, width * mul);
    }

    fitHeight() {
        this._currentFitFunction = this.fitHeight;
        const mul = this._post.canvasWidth / this._post.canvasHeight;
        let height = this._viewportHeight;
        if (!settings.get().upscaleSmallPosts) {
            height = Math.min(this._post.canvasHeight, height);
        }
        this._resize(height * mul, height);
    }

    fitBoth() {
        this._currentFitFunction = this.fitBoth;
        let mul = this._post.canvasHeight / this._post.canvasWidth;
        if (this._viewportWidth * mul < this._viewportHeight) {
            let width = this._viewportWidth;
            if (!settings.get().upscaleSmallPosts) {
                width = Math.min(this._post.canvasWidth, width);
            }
            this._resize(width, width * mul);
        } else {
            let height = this._viewportHeight;
            if (!settings.get().upscaleSmallPosts) {
                height = Math.min(this._post.canvasHeight, height);
            }
            this._resize(height / mul, height);
        }
    }

    fitOriginal() {
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
        if (window.innerWidth <= 800) {
            const buttons = document.querySelector(".sidebar > .buttons");
            if (buttons) {
                const content = document.querySelector(".content");
                content.insertBefore(buttons, content.querySelector(".post-container + *"));

                const afterControls = document.querySelector(".content > .after-mobile-controls");
                if (afterControls) {
                    afterControls.parentElement.parentElement.appendChild(afterControls);
                }
            }
        } else {
            const buttons = document.querySelector(".content > .buttons");
            if (buttons) {
                const sidebar = document.querySelector(".sidebar");
                sidebar.insertBefore(buttons, sidebar.firstElementChild);
            }

            const afterControls = document.querySelector(".content + .after-mobile-controls");
            if (afterControls) {
                document.querySelector(".content").appendChild(afterControls);
            }
        }

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
        if (["image", "flash"].includes(this._post.type)) {
            newNode.style.backgroundImage = "url("+this._post.thumbnailUrl+")";
        }
        if (this._post.type == "image") {
            newNode.firstElementChild.addEventListener("load", (e) => {
                if (settings.get().transparencyGrid) {
                    newNode.classList.add("transparency-grid");
                } else {
                    newNode.style.backgroundImage = "";
                }
            });
        } else if (settings.get().transparencyGrid) {
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
