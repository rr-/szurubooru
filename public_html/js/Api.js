var App = App || {};

App.API = function(_, jQuery, promise, appState) {

	var baseUrl = '/api/';

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

	function request(method, url, data) {
		var fullUrl = baseUrl + '/' + url;
		fullUrl = fullUrl.replace(/\/{2,}/, '/');

		return promise.make(function(resolve, reject) {
			jQuery.ajax({
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
			});
		});
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
		delete: _delete
	};

};

App.DI.registerSingleton('api', ['_', 'jQuery', 'promise', 'appState'], App.API);
