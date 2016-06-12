'use strict';

const page = require('page');
const events = require('../events.js');
const keyboard = require('../util/keyboard.js');
const misc = require('../util/misc.js');
const views = require('../util/views.js');

function _formatUrl(url, page) {
    return url.replace('{page}', page);
}

function _removeConsecutiveDuplicates(a) {
    return a.filter((item, pos, ary) => {
        return !pos || item != ary[pos - 1];
    });
}

function _getVisiblePageNumbers(currentPage, totalPages) {
    const threshold = 2;
    let pagesVisible = [];
    for (let i = 1; i <= threshold; i++) {
        pagesVisible.push(i);
    }
    for (let i = totalPages - threshold; i <= totalPages; i++) {
        pagesVisible.push(i);
    }
    for (let i = currentPage - threshold;
            i <= currentPage + threshold;
            i++) {
        pagesVisible.push(i);
    }
    pagesVisible = pagesVisible.filter((item, pos, ary) => {
        return item >= 1 && item <= totalPages;
    });
    pagesVisible = pagesVisible.sort((a, b) => { return a - b; });
    pagesVisible = _removeConsecutiveDuplicates(pagesVisible);
    return pagesVisible;
}

function _getPages(currentPage, pageNumbers, clientUrl) {
    const pages = [];
    let lastPage = 0;
    for (let page of pageNumbers) {
        if (page !== lastPage + 1) {
            pages.push({ellipsis: true});
        }
        pages.push({
            number: page,
            link: _formatUrl(clientUrl, page),
            active: currentPage === page,
        });
        lastPage = page;
    }
    return pages;
}

class ManualPageView {
    constructor() {
        this._holderTemplate = views.getTemplate('manual-pager');
        this._navTemplate = views.getTemplate('manual-pager-nav');
    }

    render(ctx) {
        const target = document.getElementById('content-holder');
        const source = this._holderTemplate();
        const pageContentHolder = source.querySelector('.page-content-holder');
        const pageHeaderHolder = source.querySelector('.page-header-holder');
        const pageNav = source.querySelector('.page-nav');
        const currentPage = ctx.searchQuery.page;

        ctx.headerContext.target = pageHeaderHolder;
        if (ctx.headerRenderer) {
            ctx.headerRenderer.render(ctx.headerContext);
        }

        ctx.requestPage(currentPage).then(response => {
            Object.assign(ctx.pageContext, response);
            ctx.pageContext.target = pageContentHolder;
            ctx.pageRenderer.render(ctx.pageContext);

            const totalPages = Math.ceil(response.total / response.pageSize);
            const pageNumbers = _getVisiblePageNumbers(currentPage, totalPages);
            const pages = _getPages(currentPage, pageNumbers, ctx.clientUrl);

            keyboard.bind(['a', 'left'], () => {
                if (currentPage > 1) {
                    page.show(_formatUrl(ctx.clientUrl, currentPage - 1));
                }
            });
            keyboard.bind(['d', 'right'], () => {
                if (currentPage < totalPages) {
                    page.show(_formatUrl(ctx.clientUrl, currentPage + 1));
                }
            });

            if (response.total) {
                views.showView(pageNav, this._navTemplate({
                    prevLink: _formatUrl(ctx.clientUrl, currentPage - 1),
                    nextLink: _formatUrl(ctx.clientUrl, currentPage + 1),
                    prevLinkActive: currentPage > 1,
                    nextLinkActive: currentPage < totalPages,
                    pages: pages,
                }));
            }

            views.listenToMessages(source);
            views.showView(target, source);
            if (response.total <= (currentPage - 1) * response.pageSize) {
                events.notify(events.Info, 'No data to show');
            }
        }, response => {
            views.listenToMessages(source);
            views.showView(target, source);
            events.notify(events.Error, response.description);
        });
    }

    unrender() {
    }
}

module.exports = ManualPageView;
