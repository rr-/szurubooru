var App = App || {};

App.Auth = function(jQuery, util, api, appState, promise) {

	var privileges = {
		register: 'register',
		listUsers: 'listUsers',
		viewAllEmailAddresses: 'viewAllEmailAddresses',
		changeAccessRank: 'changeAccessRank',
		changeOwnAvatarStyle: 'changeOwnAvatarStyle',
		changeOwnEmailAddress: 'changeOwnEmailAddress',
		changeOwnName: 'changeOwnName',
		changeOwnPassword: 'changeOwnPassword',
		changeAllAvatarStyles: 'changeAllAvatarStyles',
		changeAllEmailAddresses: 'changeAllEmailAddresses',
		changeAllNames: 'changeAllNames',
		changeAllPasswords: 'changeAllPasswords',
		deleteOwnAccount: 'deleteOwnAccount',
		deleteAllAccounts: 'deleteAllAccounts',

		listSafePosts: 'listSafePosts',
		listSketchyPosts: 'listSketchyPosts',
		listUnsafePosts: 'listUnsafePosts',
		uploadPosts: 'uploadPosts',

		listTags: 'listTags',
	};

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
	}

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
	}

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
	}

	function logout() {
		return promise.make(function(resolve, reject) {
			jQuery.removeCookie('auth');
			appState.set('loginToken', null);
			return loginAnonymous().then(resolve).fail(reject);
		});
	}

	function tryLoginFromCookie() {
		return promise.make(function(resolve, reject) {
			if (isLoggedIn()) {
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
	}

	function updateAppState(response) {
		appState.set('privileges', response.json.privileges || []);
		appState.set('loginToken', response.json.token && response.json.token.name);
		appState.set('loggedInUser', response.json.user);
		appState.set('loggedIn', response.json.user && !!response.json.user.id);
	}

	function isLoggedIn(userName) {
		if (!appState.get('loggedIn'))
			return false;
		if (typeof(userName) != 'undefined') {
			if (getCurrentUser().name != userName)
				return false;
		}
		return true;
	}

	function getCurrentUser() {
		return appState.get('loggedInUser');
	}

	function getCurrentPrivileges() {
		return appState.get('privileges');
	}

	function hasPrivilege(privilege) {
		return _.contains(getCurrentPrivileges(), privilege);
	}

	function startObservingLoginChanges(listenerName, callback) {
		appState.startObserving('loggedIn', listenerName, callback);
	}

	return {
		loginFromCredentials: loginFromCredentials,
		loginFromToken: loginFromToken,
		loginAnonymous: loginAnonymous,
		tryLoginFromCookie: tryLoginFromCookie,
		logout: logout,

		startObservingLoginChanges: startObservingLoginChanges,
		isLoggedIn: isLoggedIn,
		getCurrentUser: getCurrentUser,
		getCurrentPrivileges: getCurrentPrivileges,
		hasPrivilege: hasPrivilege,

		privileges: privileges,
	};

};

App.DI.registerSingleton('auth', App.Auth);
