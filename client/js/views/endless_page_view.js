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
        this.minOffsetShown = null;
        this.maxOffsetShown = null;
        this.totalRecords = null;
        this.currentOffset = 0;

        const offset = parseInt(ctx.parameters.offset || 0);
        const limit = parseInt(ctx.parameters.limit || ctx.defaultLimit);
        this._loadPage(ctx, offset, limit, true)
            .then(pageNode => {
                if (offset !== 0) {
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
        let topOffset = parseInt(topPageNode.getAttribute('data-offset'));
        let topLimit = parseInt(topPageNode.getAttribute('data-limit'));
        if (topOffset !== this.currentOffset) {
            router.replace(
                ctx.getClientUrlForPage(
                    topOffset,
                    topLimit === ctx.defaultLimit ? null : topLimit),
                ctx.state,
                false);
            this.currentOffset = topOffset;
        }

        if (this.totalRecords === null) {
            return;
        }
        let scrollHeight =
            document.documentElement.scrollHeight -
            document.documentElement.clientHeight;

        if (this.minOffsetShown > 0 && window.scrollY < this.threshold) {
            this._loadPage(
                ctx, this.minOffsetShown - topLimit, topLimit, false);
        } else if (this.maxOffsetShown < this.totalRecords &&
                window.scrollY + this.threshold > scrollHeight) {
            this._loadPage(
                ctx, this.maxOffsetShown, topLimit, true);
        }
    }

    _loadPage(ctx, offset, limit, append) {
        this._working++;
        return new Promise((resolve, reject) => {
            ctx.requestPage(offset, limit).then(response => {
                if (!this._active) {
                    this._working--;
                    return Promise.reject();
                }
                window.requestAnimationFrame(() => {
                    let pageNode = this._renderPage(ctx, append, response);
                    this._working--;
                    resolve(pageNode);
                });
            }, error => {
                this.showError(error.message);
                this._working--;
                reject();
            });
        });
    }

    _renderPage(ctx, append, response) {
        let pageNode = null;

        if (response.total) {
            pageNode = pageTemplate({
                totalPages: Math.ceil(response.total / response.limit),
                page: Math.ceil(
                    (response.offset + response.limit) / response.limit),
            });
            pageNode.setAttribute('data-offset', response.offset);
            pageNode.setAttribute('data-limit', response.limit);

            ctx.pageRenderer({
                parameters: ctx.parameters,
                response: response,
                hostNode: pageNode.querySelector('.page-content-holder'),
            });

            this.totalRecords = response.total;

            if (response.offset < this.minOffsetShown ||
                    this.minOffsetShown === null) {
                this.minOffsetShown = response.offset;
            }
            if (response.offset + response.results.length
                    > this.maxOffsetShown ||
                    this.maxOffsetShown === null) {
                this.maxOffsetShown =
                    response.offset + response.results.length;
            }
            response.results.addEventListener('remove', e => {
                this.maxOffsetShown--;
                this.totalRecords--;
            });

            if (append) {
                this._pagesHolderNode.appendChild(pageNode);
                if (!this._init && response.offset > 0) {
                    window.scroll(0, pageNode.getBoundingClientRect().top);
                }
            } else {
                this._pagesHolderNode.prependChild(pageNode);

                window.scroll(
                    window.scrollX,
                    window.scrollY + pageNode.offsetHeight);
            }
        } else if (!response.results.length) {
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
