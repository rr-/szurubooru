'use strict';

const events = require('../events.js');
const misc = require('../util/misc.js');
const views = require('../util/views.js');

function removeConsecutiveDuplicates(a) {
    return a.filter((item, pos, ary) => {
        return !pos || item != ary[pos - 1];
    });
}

class EndlessPageView {
    constructor() {
        this.holderTemplate = views.getTemplate('endless-pager');
        this.pageTemplate = views.getTemplate('endless-pager-page');
    }

    render(ctx) {
        const target = document.getElementById('content-holder');
        const source = this.holderTemplate();
        const pagesHolder = source.querySelector('.pages-holder');
        views.listenToMessages(target);
        views.showView(target, source);

        const threshold = window.innerHeight / 3;

        this.minPageShown = null;
        this.maxPageShown = null;
        this.totalPages = null;
        this.fetching = false;

        this.updater = () => {
            if (this.fetching || this.totalPages === null) {
                return;
            }
            let scrollHeight =
                document.documentElement.scrollHeight -
                document.documentElement.clientHeight;

            if (this.minPageShown > 1 && window.scrollY - threshold < 0) {
                this.loadPage(pagesHolder, ctx, this.minPageShown - 1, false);
            } else if (this.maxPageShown < this.totalPages &&
                    window.scrollY + threshold > scrollHeight) {
                this.loadPage(pagesHolder, ctx, this.maxPageShown + 1, true);
            }
        };

        this.loadPage(pagesHolder, ctx, ctx.initialPage, true);
        window.addEventListener('scroll', this.updater, true);
    }

    unrender() {
        window.removeEventListener('scroll', this.updater, true);
    }

    loadPage(pagesHolder, ctx, currentPage, append) {
        this.fetching = true;

        if (currentPage < this.minPageShown || this.minPageShown === null) {
            this.minPageShown = currentPage;
        }
        if (currentPage > this.maxPageShown || this.maxPageShown === null) {
            this.maxPageShown = currentPage;
        }

        ctx.requestPage(currentPage).then(response => {
            this.totalPages = Math.ceil(response.total / response.pageSize);
            if (response.total) {
                const page = this.pageTemplate({
                    page: currentPage,
                    totalPages: this.totalPages,
                });

                let pageRendererCtx = response;
                pageRendererCtx.target = page.querySelector(
                    '.page-content-holder');
                ctx.pageRenderer.render(pageRendererCtx);

                if (append) {
                    pagesHolder.appendChild(page);
                } else {
                    pagesHolder.prependChild(page);
                    window.scroll(
                        window.scrollX, window.scrollY + page.offsetHeight);
                }
            }

            this.fetching = false;
            this.updater();

            if (response.total <= (currentPage - 1) * response.pageSize) {
                events.notify(events.Info, 'No data to show');
            }
        }, response => {
            events.notify(events.Error, response.description);
        });
    }
}

module.exports = EndlessPageView;
