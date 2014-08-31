var App = App || {};

App.API = function() {

	var baseUrl = '/api/';

	function get(url, data) {
		return request('GET', url, data);
	};

	function post(url, data) {
		return request('POST', url, data);
	};

	function put(url, data) {
		return request('PUT', url, data);
	};

	function _delete(url, data) {
		return request('DELETE', url, data);
	};

	function request(method, url, data) {
		var fullUrl = baseUrl + '/' + url;
		fullUrl = fullUrl.replace(/\/{2,}/, '/');

		return new Promise(function(resolve, reject) {
			$.ajax({
				success: function(data, textStatus, xhr) {
					resolve({
						status: xhr.status,
						json: data});
				},
				error: function(xhr, textStatus, errorThrown) {
					reject({
						status: xhr.status,
						json: xhr.responseJSON
							? xhr.responseJSON
							: {error: errorThrown}});
				},
				type: method,
				url: fullUrl,
				data: data,
			});
		});
	};

	return {
		get: get,
		post: post,
		put: put,
		delete: _delete
	};

};

App.DI.registerSingleton('api', App.API);
