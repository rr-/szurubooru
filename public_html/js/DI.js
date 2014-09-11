var App = App || {};

App.DI = (function() {

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
			var deps = resolveDependencies(objectInitializer, factory.dependencies);
			instance = {};
			instance = objectInitializer.apply(instance, deps);
			if (singleton) {
				instances[key] = instance;
			}
		}
		return instance;
	}

	function resolveDependencies(objectIntializer, depKeys) {
		var deps = [];
		for (var i = 0; i < depKeys.length; i ++) {
			deps[i] = get(depKeys[i]);
		}
		return deps;
	}

	function register(key, dependencies, objectInitializer) {
		factories[key] = {initializer: objectInitializer, singleton: false, dependencies: dependencies};
	}

	function registerSingleton(key, dependencies, objectInitializer) {
		factories[key] = {initializer: objectInitializer, singleton: true, dependencies: dependencies};
	}

	function registerManual(key, objectInitializer) {
		instances[key] = objectInitializer();
	}

	return {
		get: get,
		register: register,
		registerManual: registerManual,
		registerSingleton: registerSingleton,
	};

})();
