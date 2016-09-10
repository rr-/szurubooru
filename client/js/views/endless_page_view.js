'use strict';

const router = require('../router.js');
const views = require('../util/views.js');

const holderTemplate = views.getTemplate('endless-pager');
const pageTemplate = views.getTemplate('endless-pager-page');

class EndlessPageView {
    constructor(ctx) {
        this._hostNode = document.getElementById('content-holder');
        views.replaceContent(this._hostNode, holderTemplate());
    }

    run(ctx) {
        this._active = true;
        this._working = 0;
        this._init = false;

        this.clearMessages();
        views.emptyContent(this._pagesHolderNode);

        this.threshold = window.innerHeight / 3;
        this.minPageShown = null;
        this.maxPageShown = null;
        this.totalPages = null;
        this.currentPage = null;

        this._loadPage(ctx, ctx.parameters.page, true).then(pageNode => {
            if (ctx.parameters.page !== 1) {
                pageNode.scrollIntoView();
            }
        });
        this._probePageLoad(ctx);

        views.monitorNodeRemoval(this._pagesHolderNode, () => this._destroy());
    }

    get pageHeaderHolderNode() {
        return this._hostNode.querySelector('.page-header-holder');
    }

    get _pagesHolderNode() {
        return this._hostNode.querySelector('.pages-holder');
    }

    _destroy() {
        this._active = false;
    }

    _probePageLoad(ctx) {
        if (this._active) {
            window.setTimeout(() => {
                window.requestAnimationFrame(() => {
                    this._probePageLoad(ctx);
                });
            }, 250);
        }

        if (this._working) {
            return;
        }

        let topPageNode = null;
        let element = document.elementFromPoint(
            window.innerWidth / 2,
            window.innerHeight / 2);
        while (element.parentNode !== null) {
            if (element.classList.contains('page')) {
                topPageNode = element;
                break;
            }
            element = element.parentNode;
        }
        if (!topPageNode) {
            return;
        }
        let topPageNumber = parseInt(topPageNode.getAttribute('data-page'));
        if (topPageNumber !== this.currentPage) {
            router.replace(
                ctx.getClientUrlForPage(topPageNumber),
                ctx.state,
                false);
            this.currentPage = topPageNumber;
        }

        if (this.totalPages === null) {
            return;
        }
        let scrollHeight =
            document.documentElement.scrollHeight -
            document.documentElement.clientHeight;

        if (this.minPageShown > 1 && window.scrollY < this.threshold) {
            this._loadPage(ctx, this.minPageShown - 1, false);
        } else if (this.maxPageShown < this.totalPages &&
                window.scrollY + this.threshold > scrollHeight) {
            this._loadPage(ctx, this.maxPageShown + 1, true);
        }
    }

    _loadPage(ctx, pageNumber, append) {
        this._working++;
        return new Promise((resolve, reject) => {
            ctx.requestPage(pageNumber).then(response => {
                if (!this._active) {
                    this._working--;
                    return Promise.reject();
                }
                this.totalPages = Math.ceil(response.total / response.pageSize);
                window.requestAnimationFrame(() => {
                    let pageNode = this._renderPage(
                        ctx, pageNumber, append, response);
                    this._working--;
                    resolve(pageNode);
                });
            }, response => {
                this.showError(response.description);
                this._working--;
                reject();
            });
        });
    }

    _renderPage(ctx, pageNumber, append, response) {
        let pageNode = null;

        if (response.total) {
            pageNode = pageTemplate({
                page: pageNumber,
                totalPages: this.totalPages,
            });
            pageNode.setAttribute('data-page', pageNumber);

            Object.assign(ctx.pageContext, response);
            ctx.pageContext.hostNode = pageNode.querySelector(
                '.page-content-holder');
            ctx.pageRenderer(ctx.pageContext);

            if (pageNumber < this.minPageShown ||
                    this.minPageShown === null) {
                this.minPageShown = pageNumber;
            }
            if (pageNumber > this.maxPageShown ||
                    this.maxPageShown === null) {
                this.maxPageShown = pageNumber;
            }

            if (append) {
                this._pagesHolderNode.appendChild(pageNode);
                if (!this._init && pageNumber !== 1) {
                    window.scroll(0, pageNode.getBoundingClientRect().top);
                }
            } else {
                this._pagesHolderNode.prependChild(pageNode);

                window.scroll(
                    window.scrollX,
                    window.scrollY + pageNode.offsetHeight);
            }
        } else if (response.total <= (pageNumber - 1) * response.pageSize) {
            this.showInfo('No data to show');
        }

        this._init = true;
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
