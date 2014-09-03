var App = App || {};

App.Util = (function(jQuery) {

	var templateCache = {};

	function parseComplexRouteArgs(args) {
		var result = {};
		args = (args || '').split(/;/);
		for (var i = 0; i < args.length; i ++) {
			var arg = args[i];
			if (!arg)
				continue;
			kv = arg.split(/=/);
			result[kv[0]] = kv[1];
		}
		return result;
	}

	function compileComplexRouteArgs(baseUri, args) {
		var result = baseUri + '/';
		_.each(args, function(v, k) {
			if (typeof(v) == 'undefined')
				return;
			result += k + '=' + v + ';'
		});
		result = result.slice(0, -1);
		return result;
	}

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

	function initPresenter(presenterGetter, args) {
		var presenter = presenterGetter();
		presenter.init.call(presenter, args);
	}

	function initContentPresenter(presenterGetter, args) {
		//jQuery('#content').empty();
		initPresenter(presenterGetter, args);
	};

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
		initPresenter : initPresenter,
		initContentPresenter: initContentPresenter,
		parseComplexRouteArgs: parseComplexRouteArgs,
		compileComplexRouteArgs: compileComplexRouteArgs,
	};
});

App.DI.registerSingleton('util', App.Util);
