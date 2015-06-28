var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.HistoryPresenter = function(
    _,
    jQuery,
    util,
    promise,
    auth,
    pagerPresenter,
    topNavigationPresenter) {

    var $el = jQuery('#content');
    var templates = {};
    var params;

    function init(params, loaded) {
        topNavigationPresenter.changeTitle('History');

        promise.wait(
                util.promiseTemplate('global-history'),
                util.promiseTemplate('history'))
            .then(function(historyWrapperTemplate, historyTemplate) {
                templates.historyWrapper = historyWrapperTemplate;
                templates.history = historyTemplate;

                render();
                loaded();

                pagerPresenter.init({
                        baseUri: '#/history',
                        backendUri: '/history',
                        $target: $el.find('.pagination-target'),
                        updateCallback: function($page, data) {
                            renderHistory($page, data.entities);
                        },
                    },
                    function() {
                        reinit(params, function() {});
                    });
            }).fail(function() {
                console.log(arguments);
                loaded();
            });
    }

    function reinit(_params, loaded) {
        params = _params;
        params.query = params.query || {};

        pagerPresenter.reinit({query: params.query});
        loaded();
    }

    function deinit() {
        pagerPresenter.deinit();
    }

    function render() {
        $el.html(templates.historyWrapper());
    }

    function renderHistory($page, historyItems) {
        $page.append(templates.history({
            util: util,
            history: historyItems}));
    }

    return {
        init: init,
        reinit: reinit,
        deinit: deinit,
        render: render,
    };

};

App.DI.register('historyPresenter', ['_', 'jQuery', 'util', 'promise', 'auth', 'pagerPresenter', 'topNavigationPresenter'], App.Presenters.HistoryPresenter);
