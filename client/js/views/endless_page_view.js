'use strict';

const page = require('page');
const events = require('../events.js');
const views = require('../util/views.js');

class EndlessPageView {
    constructor() {
        this.holderTemplate = views.getTemplate('endless-pager');
        this.pageTemplate = views.getTemplate('endless-pager-page');
    }

    render(ctx) {
        const target = document.getElementById('content-holder');
        const source = this.holderTemplate();
        const pageHeaderHolder = source.querySelector('.page-header-holder');
        const pagesHolder = source.querySelector('.pages-holder');
        views.listenToMessages(target);
        views.showView(target, source);
        this.active = true;
        this.working = 0;

        let headerRendererCtx = ctx;
        headerRendererCtx.target = pageHeaderHolder;
        ctx.headerRenderer.render(headerRendererCtx);

        const threshold = window.innerHeight / 3;

        this.minPageShown = null;
        this.maxPageShown = null;
        this.totalPages = null;
        this.currentPage = null;

        this.updater = () => {
            if (this.working) {
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
                this.loadPage(pagesHolder, ctx, this.minPageShown - 1, false)
                    .then(() => this.updater());
            } else if (this.maxPageShown < this.totalPages &&
                    window.scrollY + threshold > scrollHeight) {
                this.loadPage(pagesHolder, ctx, this.maxPageShown + 1, true)
                    .then(() => this.updater());
            }
        };

        this.loadPage(pagesHolder, ctx, ctx.searchQuery.page, true)
            .then(pageNode => {
                if (ctx.searchQuery.page > 1) {
                    window.scroll(0, pageNode.getBoundingClientRect().top);
                }
                this.updater();
            });
        window.addEventListener('scroll', this.updater, true);
        window.addEventListener('unload', this.scrollToTop, true);
    }

    unrender() {
        this.active = false;
        window.removeEventListener('scroll', this.updater, true);
        window.removeEventListener('unload', this.scrollToTop, true);
    }

    scrollToTop() {
        window.scroll(0, 0);
    }

    loadPage(pagesHolder, ctx, pageNumber, append) {
        this.working++;
        return ctx.requestPage(pageNumber).then(response => {
            if (!this.active) {
                this.working--;
                return Promise.reject();
            }
            this.totalPages = Math.ceil(response.total / response.pageSize);
            if (response.total) {
                const pageNode = this.pageTemplate({
                    page: pageNumber,
                    totalPages: this.totalPages,
                });
                pageNode.setAttribute('data-page', pageNumber);

                let pageRendererCtx = response;
                pageRendererCtx.target = pageNode.querySelector(
                    '.page-content-holder');
                ctx.pageRenderer.render(pageRendererCtx);

                if (pageNumber < this.minPageShown || this.minPageShown === null) {
                    this.minPageShown = pageNumber;
                }
                if (pageNumber > this.maxPageShown || this.maxPageShown === null) {
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
                this.working--;
                return Promise.resolve(pageNode);
            }
            if (response.total <= (pageNumber - 1) * response.pageSize) {
                events.notify(events.Info, 'No data to show');
            }
            this.working--;
            return Promise.reject();
        }, response => {
            events.notify(events.Error, response.description);
            this.working--;
            return Promise.reject();
        });
    }
}

module.exports = EndlessPageView;
