var App = App || {};
App.Util = App.Util || {};

App.Util.Draggable = function(jQuery) {
	function relativeDragStrategy($element) {
		var $parent = $element.parent();
		var delta;

		return {
			click: function(e) {
				delta = {
					x: $element.offset().left - e.clientX,
					y: $element.offset().top - e.clientY,
				};
			},

			update: function(e) {
				var x = e.clientX + delta.x - $parent.offset().left;
				var y = e.clientY + delta.y - $parent.offset().top;
				x = Math.min(Math.max(x, 0), $parent.outerWidth() - $element.outerWidth());
				y = Math.min(Math.max(y, 0), $parent.outerHeight() - $element.outerHeight());
				x *= 100.0 / $parent.outerWidth();
				y *= 100.0 / $parent.outerHeight();
				$element.css({
					left: x + '%',
					top: y + '%'});
			},
		};
	}

	function absoluteDragStrategy($element) {
		var delta;

		return {
			click: function(e) {
				delta = {
					x: $element.position().left - e.clientX,
					y: $element.position().top - e.clientY,
				};
			},

			update: function(e) {
				var x = e.clientX + delta.x;
				var y = e.clientY + delta.y;
				$element.css({
					left: x + 'px',
					top: y + 'px'});
			},
		};
	}

	function makeDraggable($element, dragStrategy) {
		var strategy = dragStrategy($element);

		$element.addClass('draggable');
		$element.mousedown(function(e) {
			if (e.target !== $element.get(0)) {
				return;
			}
			e.preventDefault();
			$element.addClass('dragging');

			strategy.click(e);
			jQuery(window).bind('mousemove.elemmove', function(e) {
				strategy.update(e);
			}).bind('mouseup.elemmove', function(e) {
				e.preventDefault();
				strategy.update(e);
				$element.removeClass('dragging');
				jQuery(window).unbind('mousemove.elemmove');
				jQuery(window).unbind('mouseup.elemmove');
			});
		});
	}

	return {
		makeDraggable: makeDraggable,
		absoluteDragStrategy: absoluteDragStrategy,
		relativeDragStrategy: relativeDragStrategy,
	};

};

App.DI.registerSingleton('draggable', ['jQuery'], App.Util.Draggable);
