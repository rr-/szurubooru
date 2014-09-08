var App = App || {};

App.DI = (function() {

	var STRIP_COMMENTS = /((\/\/.*$)|(\/\*[\s\S]*?\*\/))/mg;
	var ARGUMENT_NAMES = /([^\s,]+)/g;

	var factories = {};
	var instances = {};

	function get(key) {
		var instance = instances[key];
		if (!instance) {
			var factory = factories[key];
			if (!factory) {
				throw new Error('Unregistered key: ' + key);
			}
			var objectInitializer = factory.initializer;
			var singleton = factory.singleton;
			var deps = resolveDependencies(objectInitializer);
			instance = {};
			instance = objectInitializer.apply(instance, deps);
			if (singleton) {
				instances[key] = instance;
			}
		}
		return instance;
	}

	function resolveDependencies(objectIntializer) {
		var deps = [];
		var depKeys = getFunctionParameterNames(objectIntializer);
		for (var i = 0; i < depKeys.length; i ++) {
			deps[i] = get(depKeys[i]);
		}
		return deps;
	}

	function register(key, objectInitializer) {
		factories[key] = {initializer: objectInitializer, singleton: false};
	}

	function registerSingleton(key, objectInitializer) {
		factories[key] = {initializer: objectInitializer, singleton: true};
	}

	function registerManual(key, objectInitializer) {
		instances[key] = objectInitializer();
	}

	function getFunctionParameterNames(func) {
		var fnStr = func.toString().replace(STRIP_COMMENTS, '');
		var result = fnStr.slice(fnStr.indexOf('(') + 1, fnStr.indexOf(')')).match(ARGUMENT_NAMES);
		if (result === null) {
			result = [];
		}
		return result;
	}

	return {
		get: get,
		register: register,
		registerManual: registerManual,
		registerSingleton: registerSingleton,
	};

})();
