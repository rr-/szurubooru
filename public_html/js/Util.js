var App = App || {};

App.Util = (function(jQuery, promise) {

	var templateCache = {};
	var lastContentPresenterName;
	var lastContentPresenter;

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

	function initPresenter(presenterName, args) {
		var presenter = App.DI.get(presenterName);
		presenter.init.call(presenter, args);
	}

	function initContentPresenter(presenterName, args) {
		if (lastContentPresenterName != presenterName) {
			var presenter = App.DI.get(presenterName);
			var initResult = presenter.init.call(presenter, args);
			lastContentPresenterName = presenterName;
			lastContentPresenter = presenter;
		} else {
			lastContentPresenter.reinit.call(presenter, args);
		}
	};

	function promiseTemplate(templateName) {
		return promiseTemplateFromCache(templateName)
			|| promiseTemplateFromDOM(templateName)
			|| promiseTemplateWithAJAX(templateName);
	}

	function promiseTemplateFromCache(templateName) {
		if (templateName in templateCache) {
			return promise.make(function(resolve, reject) {
				resolve(templateCache[templateName]);
			});
		}
	}

	function promiseTemplateFromDOM(templateName) {
		var $template = jQuery('#' + templateName + '-template');
		if ($template.length) {
			return promise.make(function(resolve, reject) {
				resolve($template.html());
			});
		}
		return null;
	}

	function promiseTemplateWithAJAX(templateName) {
		return promise.make(function(resolve, reject) {
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
		promiseTemplate: promiseTemplate,
		initPresenter : initPresenter,
		initContentPresenter: initContentPresenter,
		parseComplexRouteArgs: parseComplexRouteArgs,
		compileComplexRouteArgs: compileComplexRouteArgs,
	};
});

App.DI.registerSingleton('util', App.Util);
