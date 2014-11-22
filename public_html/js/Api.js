var App = App || {};

App.API = function(_, jQuery, promise, appState) {

	var baseUrl = '/api/';
	var AJAX_UNSENT = 0;
	var AJAX_OPENED = 1;
	var AJAX_HEADERS_RECEIVED = 2;
	var AJAX_LOADING = 3;
	var AJAX_DONE = 4;

	var cache = {};

	function get(url, data) {
		return request('GET', url, data);
	}

	function post(url, data) {
		return request('POST', url, data);
	}

	function put(url, data) {
		return request('PUT', url, data);
	}

	function _delete(url, data) {
		return request('DELETE', url, data);
	}

	function getCacheKey(method, url, data) {
		return JSON.stringify({method: method, url: url, data: data});
	}

	function clearCache() {
		cache = {};
	}

	function request(method, url, data) {
		if (method === 'GET') {
			return requestWithCache(method, url, data);
		}
		clearCache();
		return requestWithAjax(method, url, data);
	}

	function requestWithCache(method, url, data) {
		var cacheKey = getCacheKey(method, url, data);
		if (_.has(cache, cacheKey)) {
			return promise.make(function(resolve, reject) {
				resolve(cache[cacheKey]);
			});
		}

		return promise.make(function(resolve, reject) {
			promise.wait(requestWithAjax(method, url, data))
				.then(function(response) {
					setCache(method, url, data, response);
					resolve(response);
				}).fail(function(response) {
					reject(response);
				});
		});
	}

	function setCache(method, url, data, response) {
		var cacheKey = getCacheKey(method, url, data);
		cache[cacheKey] = response;
	}

	function requestWithAjax(method, url, data) {
		var fullUrl = baseUrl + '/' + url;
		fullUrl = fullUrl.replace(/\/{2,}/, '/');

		var xhr = null;
		var apiPromise = promise.make(function(resolve, reject) {
			var options = {
				headers: {
					'X-Authorization-Token': appState.get('loginToken') || '',
				},
				success: function(data, textStatus, xhr) {
					resolve({
						status: xhr.status,
						json: stripMeta(data)});
				},
				error: function(xhr, textStatus, errorThrown) {
					reject({
						status: xhr.status,
						json: xhr.responseJSON ?
							stripMeta(xhr.responseJSON) :
							{error: errorThrown}});
				},
				type: method,
				url: fullUrl,
				data: data,
				cache: false,
			};
			if (data instanceof FormData) {
				options.processData = false;
				options.contentType = false;
			}
			xhr = jQuery.ajax(options);
		});
		apiPromise.xhr = xhr;
		return apiPromise;
	}

	function stripMeta(data) {
		var result = {};
		_.each(data, function(v, k) {
			if (!k.match(/^__/)) {
				result[k] = v;
			}
		});
		return result;
	}

	return {
		get: get,
		post: post,
		put: put,
		delete: _delete,

		AJAX_UNSENT: AJAX_UNSENT,
		AJAX_OPENED: AJAX_OPENED,
		AJAX_HEADERS_RECEIVED: AJAX_HEADERS_RECEIVED,
		AJAX_LOADING: AJAX_LOADING,
		AJAX_DONE: AJAX_DONE,
	};

};

App.DI.registerSingleton('api', ['_', 'jQuery', 'promise', 'appState'], App.API);
