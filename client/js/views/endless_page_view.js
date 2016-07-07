'use strict';

const router = require('../router.js');
const views = require('../util/views.js');

const holderTemplate = views.getTemplate('endless-pager');
const pageTemplate = views.getTemplate('endless-pager-page');

class EndlessPageView {
    constructor(ctx) {
        this._hostNode = document.getElementById('content-holder');
        this._active = true;
        this._working = 0;
        this._init = true;

        this.threshold = window.innerHeight / 3;
        this.minPageShown = null;
        this.maxPageShown = null;
        this.totalPages = null;
        this.currentPage = null;

        const sourceNode = holderTemplate();
        const pageHeaderHolderNode
            = sourceNode.querySelector('.page-header-holder');
        this._pagesHolderNode = sourceNode.querySelector('.pages-holder');
        views.replaceContent(this._hostNode, sourceNode);

        ctx.headerContext.hostNode = pageHeaderHolderNode;
        if (ctx.headerRenderer) {
            ctx.headerRenderer(ctx.headerContext);
        }

        this._loadPage(ctx, ctx.parameters.page, true);
        this._probePageLoad(ctx);
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
        ctx.requestPage(pageNumber).then(response => {
            if (!this._active) {
                this._working--;
                return Promise.reject();
            }
            this.totalPages = Math.ceil(response.total / response.pageSize);
            window.requestAnimationFrame(() => {
                this._renderPage(
                    ctx, pageNumber, append, response);
                this._working--;
            });
        }, response => {
            this.showError(response.description);
            this._working--;
        });
    }

    _renderPage(ctx, pageNumber, append, response) {
        if (response.total) {
            const pageNode = pageTemplate({
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
                if (this._init && pageNumber !== 1) {
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
        this._init = false;
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
