"use strict";

const router = require("../router.js");
const keyboard = require("../util/keyboard.js");
const views = require("../util/views.js");

const holderTemplate = views.getTemplate("manual-pager");
const navTemplate = views.getTemplate("manual-pager-nav");

function _removeConsecutiveDuplicates(a) {
    return a.filter((item, pos, ary) => {
        return !pos || item !== ary[pos - 1];
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
    for (let i = currentPage - threshold; i <= currentPage + threshold; i++) {
        pagesVisible.push(i);
    }
    pagesVisible = pagesVisible.filter((item, pos, ary) => {
        return item >= 1 && item <= totalPages;
    });
    pagesVisible = pagesVisible.sort((a, b) => {
        return a - b;
    });
    pagesVisible = _removeConsecutiveDuplicates(pagesVisible);
    return pagesVisible;
}

function _getPages(
    currentPage,
    pageNumbers,
    limit,
    defaultLimit,
    removedItems
) {
    const pages = new Map();
    let prevPage = 0;
    for (let page of pageNumbers) {
        if (page !== prevPage + 1) {
            pages.set(page - 1, { ellipsis: true });
        }
        pages.set(page, {
            number: page,
            offset:
                (page - 1) * limit - (page > currentPage ? removedItems : 0),
            limit: limit === defaultLimit ? null : limit,
            active: currentPage === page,
        });
        prevPage = page;
    }
    return pages;
}

class ManualPageView {
    constructor(ctx) {
        this._hostNode = document.getElementById("content-holder");
        views.replaceContent(this._hostNode, holderTemplate());
    }

    run(ctx) {
        const offset = parseInt(ctx.parameters.offset || 0);
        const limit = parseInt(ctx.parameters.limit || ctx.defaultLimit);
        this.clearMessages();
        views.emptyContent(this._pageNavNode);

        ctx.requestPage(offset, limit).then(
            (response) => {
                ctx.pageRenderer({
                    parameters: ctx.parameters,
                    response: response,
                    hostNode: this._pageContentHolderNode,
                });

                keyboard.bind(["a", "left"], () => {
                    this._navigateToPrevNextPage("prev");
                });
                keyboard.bind(["d", "right"], () => {
                    this._navigateToPrevNextPage("next");
                });

                let removedItems = 0;
                if (response.total) {
                    this._refreshNav(
                        offset,
                        limit,
                        response.total,
                        removedItems,
                        ctx
                    );
                }

                if (!response.results.length) {
                    this.showInfo("No data to show");
                }

                response.results.addEventListener("remove", (e) => {
                    removedItems++;
                    this._refreshNav(
                        offset,
                        limit,
                        response.total,
                        removedItems,
                        ctx
                    );
                });

                views.syncScrollPosition();
            },
            (response) => {
                this.showError(response.message);
            }
        );
    }

    get pageHeaderHolderNode() {
        return this._hostNode.querySelector(".page-header-holder");
    }

    get _pageContentHolderNode() {
        return this._hostNode.querySelector(".page-content-holder");
    }

    get _pageNavNode() {
        return this._hostNode.querySelector(".page-nav");
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

    _navigateToPrevNextPage(className) {
        const linkNode = this._hostNode.querySelector("a." + className);
        if (linkNode.classList.contains("disabled")) {
            return;
        }
        router.show(linkNode.getAttribute("href"));
    }

    _refreshNav(offset, limit, total, removedItems, ctx) {
        const currentPage = Math.floor((offset + limit - 1) / limit) + 1;
        const totalPages = Math.ceil((total - removedItems) / limit);
        const pageNumbers = _getVisiblePageNumbers(currentPage, totalPages);
        const pages = _getPages(
            currentPage,
            pageNumbers,
            limit,
            ctx.defaultLimit,
            removedItems
        );

        views.replaceContent(
            this._pageNavNode,
            navTemplate({
                getClientUrlForPage: ctx.getClientUrlForPage,
                prevPage: Math.min(totalPages, Math.max(1, currentPage - 1)),
                nextPage: Math.min(totalPages, Math.max(1, currentPage + 1)),
                currentPage: currentPage,
                totalPages: totalPages,
                pages: pages,
            })
        );
    }
}

module.exports = ManualPageView;
