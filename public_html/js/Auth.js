var App = App || {};

App.Auth = function(_, jQuery, util, api, appState, promise) {

	var privileges = {
		register: 'register',
		listUsers: 'listUsers',
		viewUsers: 'viewUsers',
		viewAllAccessRanks: 'viewAllAccessRanks',
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
		banUsers: 'banUsers',

		listPosts: 'listPosts',
		viewPosts: 'viewPosts',
		uploadPosts: 'uploadPosts',
		uploadPostsAnonymously: 'uploadPostsAnonymously',
		deletePosts: 'deletePosts',
		featurePosts: 'featurePosts',
		changePostSafety: 'changePostSafety',
		changePostSource: 'changePostSource',
		changePostTags: 'changePostTags',
		changePostContent: 'changePostContent',
		changePostThumbnail: 'changePostThumbnail',
		changePostRelations: 'changePostRelations',
		changePostFlags: 'changePostFlags',

		listComments: 'listComments',
		addComments: 'addComments',
		editOwnComments: 'editOwnComments',
		editAllComments: 'editAllComments',
		deleteOwnComments: 'deleteOwnComments',
		deleteAllComments: 'deleteAllComments',

		listTags: 'listTags',
		massTag: 'massTag',
		changeTagName: 'changeTagName',

		viewHistory: 'viewHistory',
	};

	function loginFromCredentials(userNameOrEmail, password, remember) {
		return promise.make(function(resolve, reject) {
			promise.wait(api.post('/login', {userNameOrEmail: userNameOrEmail, password: password}))
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

	function loginFromToken(token, isFromCookie) {
		return promise.make(function(resolve, reject) {
			var fd = {
				token: token,
				isFromCookie: isFromCookie
			};
			promise.wait(api.post('/login', fd))
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
			return promise.wait(loginAnonymous())
				.then(resolve)
				.fail(reject);
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

			promise.wait(loginFromToken(authCookie, true))
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
		appState.set('loggedIn', response.json.user && !!response.json.user.id);
		appState.set('loggedInUser', response.json.user);
	}

	function isLoggedIn(userName) {
		if (!appState.get('loggedIn')) {
			return false;
		}
		if (typeof(userName) !== 'undefined') {
			if (getCurrentUser().name !== userName) {
				return false;
			}
		}
		return true;
	}

	function getCurrentUser() {
		return appState.get('loggedInUser');
	}

	function getCurrentPrivileges() {
		return appState.get('privileges');
	}

	function updateCurrentUser(user) {
		if (user.id !== getCurrentUser().id) {
			throw new Error('Cannot set current user to other user this way.');
		}
		appState.set('loggedInUser', user);
	}

	function hasPrivilege(privilege) {
		return _.contains(getCurrentPrivileges(), privilege);
	}

	function startObservingLoginChanges(listenerName, callback) {
		appState.startObserving('loggedInUser', listenerName, callback);
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
		updateCurrentUser: updateCurrentUser,
		getCurrentPrivileges: getCurrentPrivileges,
		hasPrivilege: hasPrivilege,

		privileges: privileges,
	};

};

App.DI.registerSingleton('auth', ['_', 'jQuery', 'util', 'api', 'appState', 'promise'], App.Auth);
