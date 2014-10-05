var App = App || {};
App.Services = App.Services || {};

App.Services.TagList = function(jQuery) {
	var tags = [];

	jQuery.ajax({
		success: function(data, textStatus, xhr) {
			tags = data;
		},
		error: function(xhr, textStatus, errorThrown) {
			console.log(new Error(errorThrown));
		},
		type: 'GET',
		url: '/data/tags.json',
	});

	function getTags() {
		return tags;
	}

	return {
		getTags: getTags,
	};
};

App.DI.registerSingleton('tagList', ['jQuery'], App.Services.TagList);
