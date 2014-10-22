var App = App || {};

App.Promise = function(_, jQuery, progress) {

	var active = [];
	var promiseId = 0;

	function make(callback) {
		var deferred = jQuery.Deferred();
		var promise = deferred.promise();
		promise.promiseId = ++ promiseId;

		progress.start();
		callback(function() {
			try {
				deferred.resolve.apply(deferred, arguments);
				active = _.without(active, promise.promiseId);
				progress.done();
			} catch (e) {
				progress.reset();
			}
		}, function() {
			try {
				deferred.reject.apply(deferred, arguments);
				active = _.without(active, promise.promiseId);
				progress.done();
			} catch (e) {
				progress.reset();
			}
		});

		active.push(promise.promiseId);

		promise.always(function() {
			if (!_.contains(active, promise.promiseId)) {
				throw new Error('Broken promise (promise ID: ' + promise.promiseId + ')');
			}
		});

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

App.DI.registerSingleton('promise', ['_', 'jQuery', 'progress'], App.Promise);
