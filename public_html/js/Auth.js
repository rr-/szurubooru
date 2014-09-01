var App = App || {};

App.Auth = function(jQuery, api, appState) {

	function loginFromCredentials(userName, password, remember) {
		return new Promise(function(resolve, reject) {
			api.post('/login', {userName: userName, password: password})
				.then(function(response) {
					updateAppState(response);
					jQuery.cookie(
						'auth',
						response.json.token.name,
						remember ? { expires: 365 } : {});
					resolve(response);
				}).catch(function(response) {
					reject(response);
				});
		});
	};

	function loginFromToken(token) {
		return new Promise(function(resolve, reject) {
			api.post('/login', {token: token})
				.then(function(response) {
					updateAppState(response);
					resolve(response);
				}).catch(function(response) {
					reject(response);
				});
		});
	};

	function loginAnonymous() {
		return new Promise(function(resolve, reject) {
			api.post('/login')
				.then(function(response) {
					updateAppState(response);
					resolve(response);
				}).catch(function(response) {
					reject(response);
				});
		});
	};

	function logout() {
		return new Promise(function(resolve, reject) {
			appState.set('loggedIn', false);
			appState.set('loginToken', null);
			jQuery.removeCookie('auth');
			resolve();
		});
	};

	function tryLoginFromCookie() {
		return new Promise(function(resolve, reject) {
			if (appState.get('loggedIn')) {
				resolve();
				return;
			}

			var authCookie = jQuery.cookie('auth');
			if (!authCookie) {
				reject();
				return;
			}

			loginFromToken(authCookie).then(function(response) {
				resolve();
			}).catch(function(response) {
				jQuery.removeCookie('auth');
				reject();
			});
		});
	};

	function updateAppState(response) {
		appState.set('loggedIn', response.json.user && !!response.json.user.id);
		appState.set('loginToken', response.json.token && response.json.token.name);
		appState.set('loggedInUser', response.json.user);
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
