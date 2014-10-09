var App = App || {};

App.Util = function(_, jQuery, marked, promise) {

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

	function loadImagesNicely($img) {
		$img.css('opacity', '0');
		$img.bind('load', function() {
			$img.animate({opacity: 1}, 'fast');
		});
	}

	function promiseTemplate(templateName) {
		return promiseTemplateFromDOM(templateName) ||
			promiseTemplateWithAJAX(templateName);
	}

	function promiseTemplateFromDOM(templateName) {
		var $template = jQuery('#' + templateName + '-template');
		if ($template.length) {
			return promise.make(function(resolve, reject) {
				resolve(_.template($template.html()));
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
					resolve(_.template(data));
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

	function formatMarkdown(text) {
		var renderer = new marked.Renderer();

		var options = {
			renderer: renderer,
			breaks: true,
			sanitize: true,
			smartypants: true,
		};

		var preDecorator = function(text) {
			//prevent ^#... from being treated as headers, due to tag permalinks
			text = text.replace(/^#/g, '%%%#');
			return text;
		};

		var postDecorator = function(text) {
			//restore headers
			text = text.replace(/%%%/g, '');

			//search permalinks
			text = text.replace(/\[search\]((?:[^\[]|\[(?!\/?search\]))+)\[\/search\]/ig, '<a href="#/posts/query=$1"><code>$1</code></a>');
			//spoilers
			text = text.replace(/\[spoiler\]((?:[^\[]|\[(?!\/?spoiler\]))+)\[\/spoiler\]/ig, '<span class="spoiler">$1</span>');
			//strike-through
			text = text.replace(/(~~|~)([^~]+)\1/g, '<del>$2</del>');
			//post premalinks
			text = text.replace(/(^|[\s<>\(\)\[\]])@(\d+)/g, '$1<a href="#/post/$2"><code>@$2</code></a>');
			//user permalinks
			text = text.replace(/(^|[\s<>\(\)\[\]])\+([a-zA-Z0-9_-]+)/g, '$1<a href="#/user/$2"><code>+$2</code></a>');
			//tag permalinks
			text = text.replace(/(^|[\s<>\(\)\[\]])\#([^\s<>/\\]+)/g, '$1<a href="#/posts/query=$2"><code>#$2</code></a>');
			return text;
		};

		return postDecorator(marked(preDecorator(text), options));
	}

	function appendComplexRouteParam(baseUri, params) {
		var result = baseUri + '/';
		_.each(params, function(v, k) {
			if (typeof(v) !== 'undefined') {
				result += k + '=' + v + ';';
			}
		});
		return result.slice(0, -1);
	}

	return {
		promiseTemplate: promiseTemplate,
		formatRelativeTime: formatRelativeTime,
		formatFileSize: formatFileSize,
		formatMarkdown: formatMarkdown,
		enableExitConfirmation: enableExitConfirmation,
		disableExitConfirmation: disableExitConfirmation,
		isExitConfirmationEnabled: isExitConfirmationEnabled,
		transparentPixel: transparentPixel,
		loadImagesNicely: loadImagesNicely,
		appendComplexRouteParam: appendComplexRouteParam,
	};

};

App.DI.registerSingleton('util', ['_', 'jQuery', 'marked', 'promise'], App.Util);
