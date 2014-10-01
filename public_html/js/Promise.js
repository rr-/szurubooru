var App = App || {};

App.Promise = function(_, jQuery) {

	var active = [];

	function make(callback) {
		var deferred = jQuery.Deferred();
		var promise = deferred.promise();

		callback(function() {
			deferred.resolve.apply(deferred, arguments);
			active = _.without(active, promise);
		}, function() {
			deferred.reject.apply(deferred, arguments);
			active = _.without(active, promise);
		});

		promise.then(function() {
			if (!_.contains(active, promise)) {
				throw new Error('Broken promise');
			}
		});

		active.push(promise);
		return promise;
	}

	function wait() {
		var promises = arguments;
		var deferred = jQuery.Deferred();
		return jQuery.when.apply(jQuery, promises)
			.then(function() {
				return deferred.resolve.apply(deferred, arguments);
			}).fail(function() {
				return deferred.reject.apply(deferred, arguments);
			});
	}

	function abortAll() {
		active = [];
	}

	function getActive() {
		return active.length;
	}

	return {
		make: make,
		wait: wait,
		getActive: getActive,
		abortAll: abortAll,
	};

};

App.DI.registerSingleton('promise', ['_', 'jQuery'], App.Promise);
