'use strict';

const events = require('../events.js');
const misc = require('../util/misc.js');
const views = require('../util/views.js');

function removeConsecutiveDuplicates(a) {
    return a.filter((item, pos, ary) => {
        return !pos || item != ary[pos - 1];
    });
}

class ManualPageView {
    constructor() {
        this.holderTemplate = views.getTemplate('manual-pager');
        this.navTemplate = views.getTemplate('manual-pager-nav');
    }

    render(ctx) {
        const target = document.getElementById('content-holder');
        const source = this.holderTemplate();
        const pageContentHolder = source.querySelector('.page-content-holder');
        const pageNav = source.querySelector('.page-nav');

        ctx.requestPage(ctx.initialPage).then(response => {
            let pageRendererCtx = response;
            pageRendererCtx.target = pageContentHolder;
            ctx.pageRenderer.render(pageRendererCtx);

            const totalPages = Math.ceil(response.total / response.pageSize);
            const threshold = 2;

            let pagesVisible = [];
            for (let i = 1; i <= threshold; i++) {
                pagesVisible.push(i);
            }
            for (let i = totalPages - threshold; i <= totalPages; i++) {
                pagesVisible.push(i);
            }
            for (let i = ctx.initialPage - threshold;
                    i <= ctx.initialPage + threshold;
                    i++) {
                pagesVisible.push(i);
            }
            pagesVisible = pagesVisible.filter((item, pos, ary) => {
                return item >= 1 && item <= totalPages;
            });
            pagesVisible = pagesVisible.sort((a, b) => { return a - b; });
            pagesVisible = removeConsecutiveDuplicates(pagesVisible);

            const pages = [];
            let lastPage = 0;
            for (let page of pagesVisible) {
                if (page !== lastPage + 1) {
                    pages.push({ellipsis: true});
                }
                pages.push({
                    number: page,
                    link: ctx.clientUrl.format({page: page}),
                    active: ctx.initialPage === page,
                });
                lastPage = page;
            }
            views.showView(pageNav, this.navTemplate({
                prevLink: ctx.clientUrl.format({page: ctx.initialPage - 1}),
                nextLink: ctx.clientUrl.format({page: ctx.initialPage + 1}),
                prevLinkActive: ctx.initialPage > 1,
                nextLinkActive: ctx.initialPage < totalPages,
                pages: pages,
            }));
            views.listenToMessages(target);
            views.showView(target, source);
        }, response => {
            views.listenToMessages(target);
            views.showView(target, source);
            events.notify(events.Error, response.description);
        });
    }
}

module.exports = ManualPageView;
