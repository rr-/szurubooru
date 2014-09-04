var App = App || {};

App.Bootstrap = function(auth, router, util, promise) {

	util.initPresenter(function() { return App.DI.get('topNavigationPresenter'); });

	promise.wait(auth.tryLoginFromCookie())
		.then(startRouting)
		.fail(function(error) {
			promise.wait(auth.loginAnonymous())
				.then(startRouting)
				.fail(function(response) {
					console.log(response);
					alert('Fatal authentication error: ' + response.json.error);
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

App.DI.registerSingleton('bootstrap', App.Bootstrap);
App.DI.registerManual('jQuery', function() { return $; });
App.DI.get('bootstrap');
