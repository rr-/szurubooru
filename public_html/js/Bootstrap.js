var App = App || {};

App.Bootstrap = function(auth, router, util, promise, presenterManager) {

	promise.wait(
			auth.tryLoginFromCookie(),
			presenterManager.init())
		.then(startRouting)
		.fail(function(error) {
			promise.wait(auth.loginAnonymous())
				.then(startRouting)
				.fail(function(response) {
					console.log(response);
					window.alert('Fatal authentication error: ' + response.json.error);
				});
		});

	function startRouting() {
		try {
			router.start();
		} catch (err) {
			console.log(err);
		}
	}

};

App.DI.registerSingleton('bootstrap', ['auth', 'router', 'util', 'promise', 'presenterManager'], App.Bootstrap);
App.DI.registerManual('jQuery', function() { return window.$; });
App.DI.registerManual('pathJs', function() { return window.pathjs; });
App.DI.registerManual('_', function() { return window._; });
App.DI.registerManual('mousetrap', function() { return window.Mousetrap; });
App.DI.get('bootstrap');
