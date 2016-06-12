'use strict';

const router = require('../router.js');
const events = require('../events.js');
const views = require('../util/views.js');

function _formatUrl(url, page) {
    return url.replace('{page}', page);
}

class EndlessPageView {
    constructor() {
        this._holderTemplate = views.getTemplate('endless-pager');
        this._pageTemplate = views.getTemplate('endless-pager-page');
    }

    render(ctx) {
        const target = document.getElementById('content-holder');
        const source = this._holderTemplate();
        const pageHeaderHolder = source.querySelector('.page-header-holder');
        this._pagesHolder = source.querySelector('.pages-holder');
        views.listenToMessages(source);
        views.showView(target, source);
        this._active = true;
        this._working = 0;
        this._init = true;

        ctx.headerContext.target = pageHeaderHolder;
        if (ctx.headerRenderer) {
            ctx.headerRenderer.render(ctx.headerContext);
        }

        this.threshold = window.innerHeight / 3;
        this.minPageShown = null;
        this.maxPageShown = null;
        this.totalPages = null;
        this.currentPage = null;

        this._loadPage(ctx, ctx.searchQuery.page, true);
        window.addEventListener('unload', this._scrollToTop, true);
        this._probePageLoad(ctx);
    }

    unrender() {
        this._active = false;
        window.removeEventListener('unload', this._scrollToTop, true);
    }

    _scrollToTop() {
        window.scroll(0, 0);
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
                _formatUrl(ctx.clientUrl, topPageNumber),
                {},
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
            events.notify(events.Error, response.description);
            this._working--;
        });
    }

    _renderPage(ctx, pageNumber, append, response) {
        if (response.total) {
            const pageNode = this._pageTemplate({
                page: pageNumber,
                totalPages: this.totalPages,
            });
            pageNode.setAttribute('data-page', pageNumber);

            Object.assign(ctx.pageContext, response);
            ctx.pageContext.target = pageNode.querySelector(
                '.page-content-holder');
            ctx.pageRenderer.render(ctx.pageContext);

            if (pageNumber < this.minPageShown ||
                    this.minPageShown === null) {
                this.minPageShown = pageNumber;
            }
            if (pageNumber > this.maxPageShown ||
                    this.maxPageShown === null) {
                this.maxPageShown = pageNumber;
            }

            if (append) {
                this._pagesHolder.appendChild(pageNode);
                /*if (this._init && pageNumber !== 1) {
                    window.scroll(0, pageNode.getBoundingClientRect().top);
                }*/
            } else {
                this._pagesHolder.prependChild(pageNode);

                window.scroll(
                    window.scrollX,
                    window.scrollY + pageNode.offsetHeight);
            }
        } else if (response.total <= (pageNumber - 1) * response.pageSize) {
            events.notify(events.Info, 'No data to show');
        }
        this._init = false;
    }
}

module.exports = EndlessPageView;
