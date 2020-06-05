"use strict";

const router = require("../router.js");
const views = require("../util/views.js");

const holderTemplate = views.getTemplate("endless-pager");
const pageTemplate = views.getTemplate("endless-pager-page");

function isScrolledIntoView(element) {
    let top = 0;
    do {
        top += element.offsetTop || 0;
        element = element.offsetParent;
    } while (element);
    return top >= window.scrollY && top <= window.scrollY + window.innerHeight;
}

class EndlessPageView {
    constructor(ctx) {
        this._hostNode = document.getElementById("content-holder");
        views.replaceContent(this._hostNode, holderTemplate());
    }

    run(ctx) {
        this._destroy();

        this._active = true;
        this._runningRequests = 0;
        this._initialPageLoad = true;

        this.clearMessages();
        views.emptyContent(this._pagesHolderNode);

        this.minOffsetShown = null;
        this.maxOffsetShown = null;
        this.totalRecords = null;
        this.currentOffset = 0;
        this.defaultLimit = parseInt(ctx.parameters.limit || ctx.defaultLimit);

        const initialOffset = parseInt(ctx.parameters.offset || 0);
        this._loadPage(ctx, initialOffset, this.defaultLimit, true).then(
            (pageNode) => {
                if (initialOffset !== 0) {
                    pageNode.scrollIntoView();
                }
            }
        );

        this._timeout = window.setInterval(() => {
            window.requestAnimationFrame(() => {
                this._probePageLoad(ctx);
                this._syncUrl(ctx);
            });
        }, 250);

        views.monitorNodeRemoval(this._pagesHolderNode, () => this._destroy());
    }

    get pageHeaderHolderNode() {
        return this._hostNode.querySelector(".page-header-holder");
    }

    get topPageGuardNode() {
        return this._hostNode.querySelector(".page-guard.top");
    }

    get bottomPageGuardNode() {
        return this._hostNode.querySelector(".page-guard.bottom");
    }

    get _pagesHolderNode() {
        return this._hostNode.querySelector(".pages-holder");
    }

    _destroy() {
        window.clearInterval(this._timeout);
        this._active = false;
    }

    _syncUrl(ctx) {
        let topPageNode = null;
        let element = document.elementFromPoint(
            window.innerWidth / 2,
            window.innerHeight / 2
        );
        while (element.parentNode !== null) {
            if (element.classList.contains("page")) {
                topPageNode = element;
                break;
            }
            element = element.parentNode;
        }
        if (!topPageNode) {
            return;
        }
        let topOffset = parseInt(topPageNode.getAttribute("data-offset"));
        let topLimit = parseInt(topPageNode.getAttribute("data-limit"));
        if (topOffset !== this.currentOffset) {
            router.replace(
                ctx.getClientUrlForPage(
                    topOffset,
                    topLimit === ctx.defaultLimit ? null : topLimit
                ),
                ctx.state,
                false
            );
            this.currentOffset = topOffset;
        }
    }

    _probePageLoad(ctx) {
        if (!this._active || this._runningRequests) {
            return;
        }

        if (this.totalRecords === null) {
            return;
        }

        if (
            this.minOffsetShown > 0 &&
            isScrolledIntoView(this.topPageGuardNode)
        ) {
            this._loadPage(
                ctx,
                this.minOffsetShown - this.defaultLimit,
                this.defaultLimit,
                false
            );
        }

        if (
            this.maxOffsetShown < this.totalRecords &&
            isScrolledIntoView(this.bottomPageGuardNode)
        ) {
            this._loadPage(ctx, this.maxOffsetShown, this.defaultLimit, true);
        }
    }

    _loadPage(ctx, offset, limit, append) {
        this._runningRequests++;
        return new Promise((resolve, reject) => {
            ctx.requestPage(offset, limit).then(
                (response) => {
                    if (!this._active) {
                        this._runningRequests--;
                        return Promise.reject();
                    }
                    window.requestAnimationFrame(() => {
                        let pageNode = this._renderPage(ctx, append, response);
                        this._runningRequests--;
                        resolve(pageNode);
                    });
                },
                (error) => {
                    this.showError(error.message);
                    this._runningRequests--;
                    reject();
                }
            );
        });
    }

    _renderPage(ctx, append, response) {
        let pageNode = null;

        if (response.total) {
            pageNode = pageTemplate({
                totalPages: Math.ceil(response.total / response.limit),
                page: Math.ceil(
                    (response.offset + response.limit) / response.limit
                ),
            });
            pageNode.setAttribute("data-offset", response.offset);
            pageNode.setAttribute("data-limit", response.limit);

            ctx.pageRenderer({
                parameters: ctx.parameters,
                response: response,
                hostNode: pageNode.querySelector(".page-content-holder"),
            });

            this.totalRecords = response.total;

            if (
                response.offset < this.minOffsetShown ||
                this.minOffsetShown === null
            ) {
                this.minOffsetShown = response.offset;
            }
            if (
                response.offset + response.results.length >
                    this.maxOffsetShown ||
                this.maxOffsetShown === null
            ) {
                this.maxOffsetShown =
                    response.offset + response.results.length;
            }
            response.results.addEventListener("remove", (e) => {
                this.maxOffsetShown--;
                this.totalRecords--;
            });

            if (append) {
                this._pagesHolderNode.appendChild(pageNode);
                if (this._initialPageLoad && response.offset > 0) {
                    window.scroll(0, pageNode.getBoundingClientRect().top);
                }
            } else {
                this._pagesHolderNode.prependChild(pageNode);

                window.scroll(
                    window.scrollX,
                    window.scrollY + pageNode.offsetHeight
                );
            }
        } else if (!response.results.length) {
            this.showInfo("No data to show");
        }

        this._initialPageLoad = false;
        return pageNode;
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

    showInfo(message) {
        views.showInfo(this._hostNode, message);
    }
}

module.exports = EndlessPageView;
