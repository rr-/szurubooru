var App = App || {};

App.BrowsingSettings = function(
	promise,
	auth,
	api) {

	var settings = getDefaultSettings();

	auth.startObservingLoginChanges('browsing-settings', loginStateChanged);

	readFromLocalStorage();
	if (auth.isLoggedIn()) {
		loginStateChanged();
	}

	function setSettings(newSettings) {
		settings = newSettings;
		return save();
	}

	function getSettings() {
		return settings;
	}

	function getDefaultSettings() {
		return {
			hideDownvoted: true,
			endlessScroll: true,
			listPosts: {
				safe: true,
				sketchy: true,
				unsafe: true,
			},
		};
	}

	function loginStateChanged() {
		readFromUser(auth.getCurrentUser());
	}

	function readFromLocalStorage() {
		readFromString(localStorage.getItem('browsingSettings'));
	}

	function readFromUser(user) {
		readFromString(user.browsingSettings);
	}

	function readFromString(string) {
		if (!string) {
			return;
		}

		try {
			settings = JSON.parse(string);
		} catch (e) {
		}
	}

	function saveToLocalStorage() {
		localStorage.setItem('browsingSettings', JSON.stringify(settings));
	}

	function saveToUser(user) {
		var formData = {
			browsingSettings: JSON.stringify(settings),
		};
		return api.put('/users/' + user.name, formData);
	}

	function save() {
		return promise.make(function(resolve, reject) {
			saveToLocalStorage();
			if (auth.isLoggedIn()) {
				saveToUser(auth.getCurrentUser()).then(resolve).fail(reject);
			} else {
				resolve();
			}
		});
	}

	return {
		getSettings: getSettings,
		setSettings: setSettings,
	};

};

App.DI.registerSingleton('browsingSettings', ['promise', 'auth', 'api'], App.BrowsingSettings);
