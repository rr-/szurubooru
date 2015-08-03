var App = App || {};
App.Presenters = App.Presenters || {};

App.Presenters.UserPresenter = function(
    _,
    jQuery,
    util,
    promise,
    api,
    auth,
    topNavigationPresenter,
    presenterManager,
    userBrowsingSettingsPresenter,
    userAccountSettingsPresenter,
    userAccountRemovalPresenter,
    messagePresenter) {

    var $el = jQuery('#content');
    var $messages = $el;
    var templates = {};
    var user;
    var userName = null;
    var activeTab;

    function init(params, loaded) {
        promise.wait(util.promiseTemplate('user'))
            .then(function(template) {
                templates.user = template;
                reinit(params, loaded);
            }).fail(function() {
                console.log(arguments);
                loaded();
            });
    }

    function reinit(params, loaded) {
        if (params.userName !== userName) {
            userName = params.userName;
            topNavigationPresenter.select(auth.isLoggedIn(userName) ? 'my-account' : 'users');
            topNavigationPresenter.changeTitle(userName);

            promise.wait(api.get('/users/' + userName))
                .then(function(response) {
                    user = response.json.user;
                    var extendedContext = _.extend(params, {user: user});

                    presenterManager.initPresenters([
                        [userBrowsingSettingsPresenter, _.extend({}, extendedContext, {target: '#browsing-settings-target'})],
                        [userAccountSettingsPresenter, _.extend({}, extendedContext, {target: '#account-settings-target'})],
                        [userAccountRemovalPresenter, _.extend({}, extendedContext, {target: '#account-removal-target'})]],
                        function() {
                            initTabs(params);
                            loaded();
                        });

                }).fail(function(response) {
                    $el.empty();
                    messagePresenter.showError($messages, response.json && response.json.error || response);
                    loaded();
                });

        } else {
            initTabs(params);
            loaded();
        }
    }

    function initTabs(params) {
        activeTab = params.tab || 'basic-info';
        render();
    }

    function render() {
        $el.html(templates.user({
            user: user,
            isLoggedIn: auth.isLoggedIn(user.name),
            util: util,
            canChangeBrowsingSettings: userBrowsingSettingsPresenter.getPrivileges().canChangeBrowsingSettings,
            canChangeAccountSettings: _.any(userAccountSettingsPresenter.getPrivileges()),
            canDeleteAccount: userAccountRemovalPresenter.getPrivileges().canDeleteAccount}));
        $messages = $el.find('.messages');
        util.loadImagesNicely($el.find('img'));
        userBrowsingSettingsPresenter.render();
        userAccountSettingsPresenter.render();
        userAccountRemovalPresenter.render();
        changeTab(activeTab);
    }

    function changeTab(targetTab) {
        var $link = $el.find('a[data-tab=' + targetTab + ']');
        var $links = $link.closest('ul').find('a[data-tab]');
        var $tabs = $el.find('.tab-wrapper').find('.tab');
        $links.removeClass('active');
        $link.addClass('active');
        $tabs.removeClass('active');
        $tabs.filter('[data-tab=' + targetTab + ']').addClass('active');
    }

    return {
        init: init,
        reinit: reinit,
        render: render
    };

};

App.DI.register('userPresenter', ['_', 'jQuery', 'util', 'promise', 'api', 'auth', 'topNavigationPresenter', 'presenterManager', 'userBrowsingSettingsPresenter', 'userAccountSettingsPresenter', 'userAccountRemovalPresenter', 'messagePresenter'], App.Presenters.UserPresenter);
