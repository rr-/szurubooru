'use strict';

const router = require('../router.js');
const keyboard = require('../util/keyboard.js');
const misc = require('../util/misc.js');
const views = require('../util/views.js');

const holderTemplate = views.getTemplate('manual-pager');
const navTemplate = views.getTemplate('manual-pager-nav');

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

function _getPages(currentPage, pageNumbers, ctx) {
    const pages = [];
    let lastPage = 0;
    for (let page of pageNumbers) {
        if (page !== lastPage + 1) {
            pages.push({ellipsis: true});
        }
        pages.push({
            number: page,
            link: ctx.getClientUrlForPage(page),
            active: currentPage === page,
        });
        lastPage = page;
    }
    return pages;
}

class ManualPageView {
    constructor(ctx) {
        this._hostNode = document.getElementById('content-holder');
        views.replaceContent(this._hostNode, holderTemplate());
    }

    run(ctx) {
        const currentPage = ctx.parameters.page;
        this.clearMessages();
        views.emptyContent(this._pageNavNode);

        ctx.requestPage(currentPage).then(response => {
            Object.assign(ctx.pageContext, response);
            ctx.pageContext.hostNode = this._pageContentHolderNode;
            ctx.pageRenderer(ctx.pageContext);

            const totalPages = Math.ceil(response.total / response.pageSize);
            const pageNumbers = _getVisiblePageNumbers(currentPage, totalPages);
            const pages = _getPages(currentPage, pageNumbers, ctx);

            keyboard.bind(['a', 'left'], () => {
                if (currentPage > 1) {
                    router.show(ctx.getClientUrlForPage(currentPage - 1));
                }
            });
            keyboard.bind(['d', 'right'], () => {
                if (currentPage < totalPages) {
                    router.show(ctx.getClientUrlForPage(currentPage + 1));
                }
            });

            if (response.total) {
                views.replaceContent(
                    this._pageNavNode,
                    navTemplate({
                        prevLink: ctx.getClientUrlForPage(currentPage - 1),
                        nextLink: ctx.getClientUrlForPage(currentPage + 1),
                        prevLinkActive: currentPage > 1,
                        nextLinkActive: currentPage < totalPages,
                        pages: pages,
                    }));
            }

            if (response.total <= (currentPage - 1) * response.pageSize) {
                this.showInfo('No data to show');
            }

            views.syncScrollPosition();
        }, response => {
            this.showError(response.message);
        });
    }

    get pageHeaderHolderNode() {
        return this._hostNode.querySelector('.page-header-holder');
    }

    get _pageContentHolderNode() {
        return this._hostNode.querySelector('.page-content-holder');
    }

    get _pageNavNode() {
        return this._hostNode.querySelector('.page-nav');
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

module.exports = ManualPageView;
