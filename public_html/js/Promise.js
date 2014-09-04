var App = App || {};

App.Promise = (function(jQuery) {

	function make(callback)
	{
		var deferred = jQuery.Deferred();
		callback(deferred.resolve, deferred.reject);
		return deferred.promise();
	}

	function wait(promise) {
		return jQuery.when(promise);
	}

	function waitAll() {
		return jQuery.when.apply(jQuery, arguments);
	}

	return {
		make: make,
		wait: wait,
		waitAll: waitAll,
	};

});

App.DI.registerSingleton('promise', App.Promise);
