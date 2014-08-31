var App = App || {};

App.Bootstrap = function(auth, router) {

	auth.tryLoginFromCookie()
		.then(startRouting)
		.catch(function(error) {
			auth.loginAnonymous()
				.then(startRouting)
				.catch(function(response) {
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

	return {
		startRouting: startRouting,
	};

};

App.DI.registerSingleton('bootstrap', App.Bootstrap);
App.DI.registerManual('jQuery', function() { return $; });

var bootstrap = App.DI.get('bootstrap');
