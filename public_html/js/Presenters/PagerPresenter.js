var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.PagerPresenter = function(
    _,
    jQuery,
    util,
    promise,
    keyboard,
    router,
    pager,
    messagePresenter,
    browsingSettings,
    progress) {

    var $target;
    var $pageList;
    var $messages;
    var targetContent;
    var endlessScroll = browsingSettings.getSettings().endlessScroll;
    var scrollInterval;
    var templates = {};
    var forceClear = !endlessScroll;

    var baseUri;
    var updateCallback;

    function init(params, loaded) {
        baseUri = params.baseUri;
        updateCallback = params.updateCallback;

        messagePresenter.instant = true;

        $target = params.$target;
        targetContent = jQuery(params.$target).html();

        pager.init({url: params.backendUri});
        setQuery(params.query);
        if (forceClear) {
            clearContent();
        }

        promise.wait(util.promiseTemplate('pager'))
            .then(function(template) {
                templates.pager = template;
                render();
                loaded();
            }).fail(function() {
                console.log(arguments);
                loaded();
            });
    }

    function reinit(params, loaded) {
        setQuery(params.query);
        if (forceClear) {
            clearContent();
        }

        promise.wait(retrieve())
            .then(loaded)
            .fail(loaded);

        if (!endlessScroll) {
            keyboard.keydown(['a', 'left'], navigateToPrevPage);
            keyboard.keydown(['d', 'right'], navigateToNextPage);
        }
    }

    function deinit() {
        detachNextPageLoader();
    }

    function getUrl(options) {
        return util.appendComplexRouteParam(
            baseUri,
            util.simplifySearchQuery(
                _.extend(
                    {},
                    pager.getSearchParams(),
                    {page: pager.getPage()},
                    options)));
    }

    function syncUrl(options) {
        router.navigate(getUrl(options));
    }

    function syncUrlInplace(options) {
        router.navigateInplace(getUrl(options));
    }

    function retrieve() {
        messagePresenter.hideMessages($messages);
        progress.start();

        return promise.make(function(resolve, reject) {
            hidePageList();

            promise.wait(pager.retrieve())
                .then(function(response) {
                    progress.done();

                    if (forceClear) {
                        clearContent();
                        window.scrollTo(0, 0);
                    }
                    var $page = jQuery('<div class="page">');
                    if (endlessScroll && pager.getTotalPages() > 1) {
                        $page.append('<p>Page ' + pager.getPage() + ' of ' + pager.getTotalPages() + '</p>');
                    }
                    $page.append(targetContent);
                    $target.find('.pagination-content').append($page);
                    updateCallback($page, response);

                    refreshPageList();

                    var entities =
                        response.json.posts ||
                        response.json.users ||
                        response.json.comments ||
                        response.json.tags ||
                        response.json.history;

                    if (!entities.length) {
                        messagePresenter.showInfo($messages, 'No data to show');
                        if (pager.getVisiblePages().length === 1) {
                            hidePageList();
                        } else  {
                            showPageList();
                        }
                    } else {
                        showPageList();
                    }

                    if (pager.getPage() < pager.getTotalPages()) {
                        attachNextPageLoader();
                    }

                    resolve();
                }).fail(function(response) {
                    progress.done();
                    clearContent();
                    hidePageList();
                    messagePresenter.showError($messages, response.json && response.json.error || response);

                    reject();
                });
        });
    }

    function clearContent() {
        detachNextPageLoader();
        $target.find('.pagination-content').empty();
    }

    function attachNextPageLoader() {
        if (!endlessScroll) {
            return;
        }

        detachNextPageLoader();
        scrollInterval = window.setInterval(function() {
            var myScrollInterval = scrollInterval;
            var baseLine = $target.offset().top + $target.innerHeight();
            var scrollY = jQuery(window).scrollTop() + jQuery(window).height();
            if (scrollY > baseLine) {
                syncUrlInplace({page: pager.getPage() + 1});
                window.clearInterval(myScrollInterval);
            }
        }, 100);
    }

    function detachNextPageLoader() {
        window.clearInterval(scrollInterval);
    }

    function showPageList() {
        $pageList.show();
    }

    function hidePageList() {
        $pageList.hide();
    }

    function navigateToPrevPage() {
        console.log('!');
        if (pager.prevPage()) {
            syncUrl({page: pager.getPage()});
        }
    }

    function navigateToNextPage() {
        if (pager.nextPage()) {
            syncUrl({page: pager.getPage()});
        }
    }

    function refreshPageList() {
        var $lastItem = $pageList.find('li:last-child');
        var currentPage = pager.getPage();
        var pages = pager.getVisiblePages();
        $pageList.find('li.page').remove();
        var lastPage = 0;
        _.each(pages, function(page) {
            if (page - lastPage > 1) {
                jQuery('<li class="page ellipsis"><a>&hellip;</a></li>').insertBefore($lastItem);
            }
            lastPage = page;

            var $a = jQuery('<a href="#"/>');
            $a.click(function(e) {
                e.preventDefault();
                syncUrl({page: page});
            });
            $a.addClass('big-button');
            $a.text(page);
            if (page === currentPage) {
                $a.addClass('active');
            }
            jQuery('<li class="page"/>').append($a).insertBefore($lastItem);
        });

        $pageList.find('li.next a').unbind('click').bind('click', function(e) {
            e.preventDefault();
            navigateToNextPage();
        });
        $pageList.find('li.prev a').unbind('click').bind('click', function(e) {
            e.preventDefault();
            navigateToPrevPage();
        });
    }

    function render() {
        $target.html(templates.pager());
        $messages = $target.find('.pagination-content');
        $pageList = $target.find('.page-list');
        if (endlessScroll) {
            $pageList.remove();
        } else {
            refreshPageList();
        }
    }

    function setQuery(query) {
        if (!query) {
            return;
        }
        query.page = parseInt(query.page) || 1;
        var page = query.page;
        query = _.extend({}, query);
        delete query.page;
        forceClear =
            query.query !== pager.getSearchParams().query ||
            query.order !== pager.getSearchParams().order ||
            parseInt(page) !== pager.getPage() + 1 ||
            !endlessScroll;
        pager.setSearchParams(query);
        pager.setPage(page);
    }

    function setQueryAndSyncUrl(query) {
        setQuery(query);
        syncUrl();
    }

    return {
        init: init,
        reinit: reinit,
        deinit: deinit,
        syncUrl: syncUrl,
        setQuery: setQueryAndSyncUrl,
    };

};

App.DI.register('pagerPresenter', ['_', 'jQuery', 'util', 'promise', 'keyboard', 'router', 'pager', 'messagePresenter', 'browsingSettings', 'progress'], App.Presenters.PagerPresenter);
