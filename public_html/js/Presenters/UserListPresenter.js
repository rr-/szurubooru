var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.UserListPresenter = function(
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
    var privileges = {};

    function init(params, loaded) {
        topNavigationPresenter.select('users');
        topNavigationPresenter.changeTitle('Users');

        privileges.canViewUsers = auth.hasPrivilege(auth.privileges.viewUsers);

        promise.wait(
                util.promiseTemplate('user-list'),
                util.promiseTemplate('user-list-item'))
            .then(function(listTemplate, listItemTemplate) {
                templates.list = listTemplate;
                templates.listItem = listItemTemplate;

                render();
                loaded();

                pagerPresenter.init({
                        baseUri: '#/users',
                        backendUri: '/users',
                        $target: $el.find('.pagination-target'),
                        updateCallback: function($page, data) {
                            renderUsers($page, data.entities);
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
        params.query.order = params.query.order || 'name,asc';
        updateActiveOrder(params.query.order);

        pagerPresenter.reinit({query: params.query});
        loaded();
    }

    function deinit() {
        pagerPresenter.deinit();
    }

    function render() {
        $el.html(templates.list(privileges));
    }

    function updateActiveOrder(activeOrder) {
        $el.find('.order li a.active').removeClass('active');
        $el.find('.order [href*="' + activeOrder + '"]').addClass('active');
    }

    function renderUsers($page, users) {
        var $target = $page.find('.users');
        _.each(users, function(user) {
            var $item = jQuery('<li>' + templates.listItem(_.extend({
                user: user,
                util: util,
            }, privileges)) + '</li>');
            $target.append($item);
        });
        _.map(_.map($target.find('img'), jQuery), util.loadImagesNicely);
    }

    return {
        init: init,
        reinit: reinit,
        deinit: deinit,
        render: render,
    };

};

App.DI.register('userListPresenter', ['_', 'jQuery', 'util', 'promise', 'auth', 'pagerPresenter', 'topNavigationPresenter'], App.Presenters.UserListPresenter);
