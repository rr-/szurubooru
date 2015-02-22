var App = App || {};
App.Util = App.Util || {};

App.Util.Resizable = function(jQuery) {
	function relativeResizeStrategy($element) {
		var $parent = $element.parent();
		var delta;
		var width = $element.width();
		var height = $element.height();

		var getSize = function() {
			return {width: width, height: height};
		};

		var setSize = function(newWidth, newHeight) {
			width = newWidth;
			height = newHeight;
			var screenWidth = Math.min(Math.max(width, 20), $parent.outerWidth() + $parent.offset().left - $element.offset().left);
			var screenHeight = Math.min(Math.max(height, 20), $parent.outerHeight() + $parent.offset().top - $element.offset().top);
			screenWidth *= 100.0 / $parent.outerWidth();
			screenHeight *= 100.0 / $parent.outerHeight();
			$element.css({
				width: screenWidth + '%',
				height: screenHeight + '%'});
		};

		return {
			mouseClicked: function(e) {
				delta = {
					x: $element.width() - e.clientX,
					y: $element.height() - e.clientY,
				};
			},

			mouseMoved: function(e) {
				setSize(
					e.clientX + delta.x,
					e.clientY + delta.y);
			},

			getSize: getSize,
			setSize: setSize,
		};
	}

	function makeResizable($element) {
		var $resizer = jQuery('<div class="resizer"></div>');
		var strategy = relativeResizeStrategy($element);
		$element.append($resizer);

		$resizer.mousedown(function(e) {
			e.preventDefault();
			e.stopPropagation();
			$element.addClass('resizing');

			strategy.mouseClicked(e);

			jQuery(window).bind('mousemove.elemsize', function(e) {
				strategy.mouseMoved(e);
			}).bind('mouseup.elemsize', function(e) {
				e.preventDefault();
				strategy.mouseMoved(e);
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
