var App = App || {};

App.Util = function(_, jQuery, promise) {

	var templateCache = {};
	var exitConfirmationEnabled = false;

	function transparentPixel() {
		return 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
	}

	function enableExitConfirmation() {
		exitConfirmationEnabled = true;
		jQuery(window).bind('beforeunload', function(e) {
			return 'There are unsaved changes.';
		});
	}

	function disableExitConfirmation() {
		exitConfirmationEnabled = false;
		jQuery(window).unbind('beforeunload');
	}

	function isExitConfirmationEnabled() {
		return exitConfirmationEnabled;
	}

	function parseComplexRouteArgs(args) {
		var result = {};
		args = (args || '').split(/;/);
		for (var i = 0; i < args.length; i ++) {
			var arg = args[i];
			if (!arg) {
				continue;
			}
			var kv = arg.split(/=/);
			result[kv[0]] = kv[1];
		}
		return result;
	}

	function compileComplexRouteArgs(baseUri, args) {
		var result = baseUri + '/';
		_.each(args, function(v, k) {
			if (typeof(v) !== 'undefined') {
				result += k + '=' + v + ';';
			}
		});
		result = result.slice(0, -1);
		return result;
	}

	function promiseTemplate(templateName) {
		return promiseTemplateFromCache(templateName) ||
			promiseTemplateFromDOM(templateName) ||
			promiseTemplateWithAJAX(templateName);
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

			jQuery.ajax({
				url: templateUrl,
				method: 'GET',
				success: function(data, textStatus, xhr) {
					resolve(data);
				},
				error: function(xhr, textStatus, errorThrown) {
					console.log(new Error('Error while loading template ' + templateName + ': ' + errorThrown));
					reject();
				},
			});
		});
	}

	function formatRelativeTime(timeString) {
		if (!timeString) {
			return 'never';
		}

		var then = Date.parse(timeString);
		var now = Date.now();
		var difference = Math.abs(now - then);
		var future = now < then;

		var text = (function(difference) {
			var mul = 1000;
			var prevMul;

			mul *= 60;
			if (difference < mul) {
				return 'a few seconds';
			} else if (difference < mul * 2) {
				return 'a minute';
			}

			prevMul = mul; mul *= 60;
			if (difference < mul) {
				return Math.round(difference / prevMul) + ' minutes';
			} else if (difference < mul * 2) {
				return 'an hour';
			}

			prevMul = mul; mul *= 24;
			if (difference < mul) {
				return Math.round(difference / prevMul) + ' hours';
			} else if (difference < mul * 2) {
				return 'a day';
			}

			prevMul = mul; mul *= 30.42;
			if (difference < mul) {
				return Math.round(difference / prevMul) + ' days';
			} else if (difference < mul * 2) {
				return 'a month';
			}

			prevMul = mul; mul *= 12;
			if (difference < mul) {
				return Math.round(difference / prevMul) + ' months';
			} else if (difference < mul * 2) {
				return 'a year';
			}

			return Math.round(difference / mul) + ' years';
		})(difference);

		if (text === 'a day') {
			return future ? 'tomorrow' : 'yesterday';
		}
		return future ? 'in ' + text : text + ' ago';
	}

	function formatUnits(number, base, suffixes, callback) {
		if (!number) {
			return NaN;
		}
		number *= 1.0;

		var suffix = suffixes.shift();
		while (number >= base && suffixes.length > 0) {
			suffix = suffixes.shift();
			number /= base;
		}

		if (typeof(callback) === 'undefined') {
			callback = function(number, suffix) {
				return suffix ? number.toFixed(1) + suffix : number;
			};
		}

		return callback(number, suffix);
	}

	function formatFileSize(fileSize) {
		return formatUnits(
			fileSize,
			1024,
			['B', 'K', 'M', 'G'],
			function(number, suffix) {
				var decimalPlaces = number < 20 && suffix !== 'B' ? 1 : 0;
				return number.toFixed(decimalPlaces) + suffix;
			});
	}

	return {
		promiseTemplate: promiseTemplate,
		parseComplexRouteArgs: parseComplexRouteArgs,
		compileComplexRouteArgs: compileComplexRouteArgs,
		formatRelativeTime: formatRelativeTime,
		formatFileSize: formatFileSize,
		enableExitConfirmation: enableExitConfirmation,
		disableExitConfirmation: disableExitConfirmation,
		isExitConfirmationEnabled: isExitConfirmationEnabled,
		transparentPixel: transparentPixel,
	};

};

App.DI.registerSingleton('util', ['_', 'jQuery', 'promise'], App.Util);
