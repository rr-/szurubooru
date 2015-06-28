var App = App || {};

App.Bootstrap = function(auth, router, promise, presenterManager) {

    promise.wait(presenterManager.init())
        .then(function() {
            promise.wait(auth.tryLoginFromCookie())
                .then(startRouting)
                .fail(function(error) {
                    promise.wait(auth.loginAnonymous())
                        .then(startRouting)
                        .fail(function() {
                            console.log(arguments);
                            window.alert('Fatal authentication error');
                        });
                });
        }).fail(function() {
            console.log(arguments);
        });

    function startRouting() {
        try {
            router.start();
        } catch (err) {
            console.log(err);
        }
    }

};

App.DI.registerSingleton('bootstrap', ['auth', 'router', 'promise', 'presenterManager'], App.Bootstrap);
App.DI.registerManual('jQuery', function() { return window.$; });
App.DI.registerManual('pathJs', function() { return window.pathjs; });
App.DI.registerManual('_', function() { return window._; });
App.DI.registerManual('mousetrap', function() { return window.Mousetrap; });
App.DI.registerManual('marked', function() { return window.marked; });
App.DI.registerManual('nprogress', function() { return window.NProgress; });
App.DI.get('bootstrap');
