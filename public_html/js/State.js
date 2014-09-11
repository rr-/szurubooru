var App = App || {};

App.State = function() {

	var properties = {};
	var observers = {};

	function get(key) {
		return properties[key];
	}

	function set(key, value) {
		properties[key] = value;
		if (key in observers) {
			for (var observerName in observers[key]) {
				if (observers[key].hasOwnProperty(observerName)) {
					observers[key][observerName](key, value);
				}
			}
		}
	}

	function startObserving(key, observerName, callback) {
		if (!(key in observers)) {
			observers[key] = {};
		}
		if (!(observerName in observers[key])) {
			observers[key][observerName] = {};
		}
		observers[key][observerName] = callback;
	}

	function stopObserving(key, observerName) {
		if (!(key in observers)) {
			return;
		}
		if (!(observerName in observers[key])) {
			return;
		}
		delete observers[key][observerName];
	}

	return {
		get: get,
		set: set,
		startObserving: startObserving,
		stopObserving: stopObserving,
	};

};

App.DI.registerSingleton('appState', [], App.State);
