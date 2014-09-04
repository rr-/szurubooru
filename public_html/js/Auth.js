var App = App || {};

App.Auth = function(jQuery, util, api, appState, promise) {

	function loginFromCredentials(userName, password, remember) {
		return promise.make(function(resolve, reject) {
			promise.wait(api.post('/login', {userName: userName, password: password}))
				.then(function(response) {
					updateAppState(response);
					jQuery.cookie(
						'auth',
						response.json.token.name,
						remember ? { expires: 365 } : {});
					resolve(response);
				}).fail(function(response) {
					reject(response);
				});
		});
	};

	function loginFromToken(token) {
		return promise.make(function(resolve, reject) {
			promise.wait(api.post('/login', {token: token}))
				.then(function(response) {
					updateAppState(response);
					resolve(response);
				}).fail(function(response) {
					reject(response);
				});
		});
	};

	function loginAnonymous() {
		return promise.make(function(resolve, reject) {
			promise.wait(api.post('/login'))
				.then(function(response) {
					updateAppState(response);
					resolve(response);
				}).fail(function(response) {
					reject(response);
				});
		});
	};

	function logout() {
		return promise.make(function(resolve, reject) {
			appState.set('loggedIn', false);
			appState.set('loginToken', null);
			jQuery.removeCookie('auth');
			resolve();
		});
	};

	function tryLoginFromCookie() {
		return promise.make(function(resolve, reject) {
			if (appState.get('loggedIn')) {
				resolve();
				return;
			}

			var authCookie = jQuery.cookie('auth');
			if (!authCookie) {
				reject();
				return;
			}

			promise.wait(loginFromToken(authCookie))
				.then(function(response) {
					resolve();
				}).fail(function(response) {
					jQuery.removeCookie('auth');
					reject();
				});
		});
	};

	function updateAppState(response) {
		appState.set('loginToken', response.json.token && response.json.token.name);
		appState.set('loggedInUser', response.json.user);
		appState.set('loggedIn', response.json.user && !!response.json.user.id);
	}

	return {
		loginFromCredentials: loginFromCredentials,
		loginFromToken: loginFromToken,
		loginAnonymous: loginAnonymous,
		tryLoginFromCookie: tryLoginFromCookie,
		logout: logout,
	};

};

App.DI.registerSingleton('auth', App.Auth);
