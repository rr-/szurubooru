'use strict';

const page = require('page');
const events = require('../events.js');
const views = require('../util/views.js');

class EndlessPageView {
    constructor() {
        this._holderTemplate = views.getTemplate('endless-pager');
        this._pageTemplate = views.getTemplate('endless-pager-page');
    }

    render(ctx) {
        const target = document.getElementById('content-holder');
        const source = this._holderTemplate();
        const pageHeaderHolder = source.querySelector('.page-header-holder');
        const pagesHolder = source.querySelector('.pages-holder');
        views.listenToMessages(source);
        views.showView(target, source);
        this._active = true;
        this._working = 0;

        let headerRendererCtx = ctx;
        headerRendererCtx.target = pageHeaderHolder;
        ctx.headerRenderer.render(headerRendererCtx);

        const threshold = window.innerHeight / 3;

        this.minPageShown = null;
        this.maxPageShown = null;
        this.totalPages = null;
        this.currentPage = null;

        this._updater = () => {
            if (this._working) {
                return;
            }

            let topPageNode = null;
            let element = document.elementFromPoint(window.innerWidth / 2, 1);
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
                page.replace(
                    ctx.clientUrl.format({page: topPageNumber}),
                    null,
                    false,
                    false);
                this.currentPage = topPageNumber;
            }

            if (this.totalPages === null) {
                return;
            }
            let scrollHeight =
                document.documentElement.scrollHeight -
                document.documentElement.clientHeight;

            if (this.minPageShown > 1 && window.scrollY - threshold < 0) {
                this._loadPage(pagesHolder, ctx, this.minPageShown - 1, false)
                    .then(() => this._updater());
            } else if (this.maxPageShown < this.totalPages &&
                    window.scrollY + threshold > scrollHeight) {
                this._loadPage(pagesHolder, ctx, this.maxPageShown + 1, true)
                    .then(() => this._updater());
            }
        };

        this._loadPage(pagesHolder, ctx, ctx.searchQuery.page, true)
            .then(pageNode => {
                if (ctx.searchQuery.page > 1) {
                    window.scroll(0, pageNode.getBoundingClientRect().top);
                }
                this._updater();
            });
        window.addEventListener('scroll', this._updater, true);
        window.addEventListener('unload', this._scrollToTop, true);
    }

    unrender() {
        this._active = false;
        window.removeEventListener('scroll', this._updater, true);
        window.removeEventListener('unload', this._scrollToTop, true);
    }

    _scrollToTop() {
        window.scroll(0, 0);
    }

    _loadPage(pagesHolder, ctx, pageNumber, append) {
        this._working++;
        return ctx.requestPage(pageNumber).then(response => {
            if (!this._active) {
                this._working--;
                return Promise.reject();
            }
            this.totalPages = Math.ceil(response.total / response.pageSize);
            if (response.total) {
                const pageNode = this._pageTemplate({
                    page: pageNumber,
                    totalPages: this.totalPages,
                });
                pageNode.setAttribute('data-page', pageNumber);

                let pageRendererCtx = response;
                pageRendererCtx.target = pageNode.querySelector(
                    '.page-content-holder');
                ctx.pageRenderer.render(pageRendererCtx);

                if (pageNumber < this.minPageShown ||
                        this.minPageShown === null) {
                    this.minPageShown = pageNumber;
                }
                if (pageNumber > this.maxPageShown ||
                        this.maxPageShown === null) {
                    this.maxPageShown = pageNumber;
                }

                if (append) {
                    pagesHolder.appendChild(pageNode);
                } else {
                    pagesHolder.prependChild(pageNode);

                    // note: with probability of 75%, if the user has scrolled
                    // with a mouse wheel, chrome 49 doesn't give a slightest
                    // fuck about this and loads all the way to page 1 at once
                    window.scroll(
                        window.scrollX,
                        window.scrollY + pageNode.offsetHeight);
                }
                this._working--;
                return Promise.resolve(pageNode);
            }
            if (response.total <= (pageNumber - 1) * response.pageSize) {
                events.notify(events.Info, 'No data to show');
            }
            this._working--;
            return Promise.reject();
        }, response => {
            events.notify(events.Error, response.description);
            this._working--;
            return Promise.reject();
        });
    }
}

module.exports = EndlessPageView;
