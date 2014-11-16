var App = App || {};
App.Util = App.Util || {};

App.Util.Resizable = function(jQuery) {
	function makeResizable($element) {
		var $resizer = jQuery('<div class="resizer"></div>');
		$element.append($resizer);

		$resizer.mousedown(function(e) {
			e.preventDefault();
			e.stopPropagation();
			$element.addClass('resizing');

			var $parent = $element.parent();
			var deltaX = $element.width() - e.clientX;
			var deltaY = $element.height() - e.clientY;

			var update = function(e) {
				var w = e.clientX + deltaX;
				var h = e.clientY + deltaY;
				w = Math.min(Math.max(w, 20), $parent.outerWidth() + $parent.offset().left - $element.offset().left);
				h = Math.min(Math.max(h, 20), $parent.outerHeight() + $parent.offset().top - $element.offset().top);
				w *= 100.0 / $parent.outerWidth();
				h *= 100.0 / $parent.outerHeight();
				$element.css({
					width: w + '%',
					height: h + '%'});
			};

			jQuery(window).bind('mousemove.elemsize', function(e) {
				update(e);
			}).bind('mouseup.elemsize', function(e) {
				e.preventDefault();
				update(e);
				$element.removeClass('resizing');
				jQuery(window).unbind('mousemove.elemsize');
				jQuery(window).unbind('mouseup.elemsize');
			});
		});
	}

	return {
		makeResizable: makeResizable,
	};

};

App.DI.registerSingleton('resizable', ['jQuery'], App.Util.Resizable);
