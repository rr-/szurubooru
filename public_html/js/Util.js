var App = App || {};

App.Util = (function(jQuery) {

	var templateCache = {};

	function loadTemplate(templateName) {
		return loadTemplateFromCache(templateName)
			|| loadTemplateFromDOM(templateName)
			|| loadTemplateWithAJAX(templateName);
	}

	function loadTemplateFromCache(templateName) {
		if (templateName in templateCache) {
			return new Promise(function(resolve, reject) {
				resolve(templateCache[templateName]);
			});
		}
	}

	function loadTemplateFromDOM(templateName) {
		var $template = jQuery('#' + templateName + '-template');
		if ($template.length) {
			return new Promise(function(resolve, reject) {
				resolve($template.html());
			});
		}
		return null;
	}

	function loadTemplateWithAJAX(templateName) {
		return new Promise(function(resolve, reject) {
			var templatesDir = '/templates';
			var templateUrl = templatesDir + '/' + templateName + '.tpl';
			var templateString;

			$.ajax({
				url: templateUrl,
				method: 'GET',
				success: function(data, textStatus, xhr) {
					resolve(data);
				},
				error: function(xhr, textStatus, errorThrown) {
					console.log(Error('Error while loading template ' +  templateName + ': ' + errorThrown));
					reject();
				},
			});
		});
	}

	return {
		loadTemplate: loadTemplate,
	};
});

App.DI.registerSingleton('util', App.Util);
