var App = App || {};
App.Util = App.Util || {};

App.Util.Draggable = function(jQuery) {
    var KEY_LEFT = 37;
    var KEY_UP = 38;
    var KEY_RIGHT = 39;
    var KEY_DOWN = 40;

    function relativeDragStrategy($element) {
        var $parent = $element.parent();
        var delta;
        var x = $element.offset().left - $parent.offset().left;
        var y = $element.offset().top - $parent.offset().top;

        var getPosition = function() {
            return {x: x, y: y};
        };

        var setPosition = function(newX, newY) {
            x = newX;
            y = newY;
            var screenX = Math.min(Math.max(newX, 0), $parent.outerWidth() - $element.outerWidth());
            var screenY = Math.min(Math.max(newY, 0), $parent.outerHeight() - $element.outerHeight());
            screenX *= 100.0 / $parent.outerWidth();
            screenY *= 100.0 / $parent.outerHeight();
            $element.css({
                left: screenX + '%',
                top: screenY + '%'});
        };

        return {
            mouseClicked: function(e) {
                delta = {
                    x: $element.offset().left - e.clientX,
                    y: $element.offset().top - e.clientY,
                };
            },

            mouseMoved: function(e) {
                setPosition(
                    e.clientX + delta.x - $parent.offset().left,
                    e.clientY + delta.y - $parent.offset().top);
            },

            getPosition: getPosition,
            setPosition: setPosition,
        };
    }

    function absoluteDragStrategy($element) {
        var delta;
        var x = $element.offset().left;
        var y = $element.offset().top;

        var getPosition = function() {
            return {x: x, y: y};
        };

        var setPosition = function(newX, newY) {
            x = newX;
            y = newY;
            $element.css({
                left: x + 'px',
                top: y + 'px'});
        };

        return {
            mouseClicked: function(e) {
                delta = {
                    x: $element.position().left - e.clientX,
                    y: $element.position().top - e.clientY,
                };
            },

            mouseMoved: function(e) {
                setPosition(e.clientX + delta.x, e.clientY + delta.y);
            },

            getPosition: getPosition,
            setPosition: setPosition,
        };
    }

    function makeDraggable($element, dragStrategy, enableHotkeys) {
        var strategy = dragStrategy($element);
        $element.data('drag-strategy', strategy);

        $element.addClass('draggable');

        $element.mousedown(function(e) {
            if (e.target !== $element.get(0)) {
                return;
            }
            e.preventDefault();
            $element.focus();
            $element.addClass('dragging');

            strategy.mouseClicked(e);
            jQuery(window).bind('mousemove.elemmove', function(e) {
                strategy.mouseMoved(e);
            }).bind('mouseup.elemmove', function(e) {
                e.preventDefault();
                strategy.mouseMoved(e);
                $element.removeClass('dragging');
                jQuery(window).unbind('mousemove.elemmove');
                jQuery(window).unbind('mouseup.elemmove');
            });
        });

        if (enableHotkeys) {
            $element.keydown(function(e) {
                var position = strategy.getPosition();
                var oldPosition = {x: position.x, y: position.y};
                if (e.shiftKey) {
                    return;
                }

                var delta = e.ctrlKey ? 10 : 1;
                if (e.which === KEY_LEFT) {
                    position.x -= delta;
                } else if (e.which === KEY_RIGHT) {
                    position.x += delta;
                } else if (e.which === KEY_UP) {
                    position.y -= delta;
                } else if (e.which === KEY_DOWN) {
                    position.y += delta;
                }

                if (position.x !== oldPosition.x || position.y !== oldPosition.y) {
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    e.preventDefault();
                    strategy.setPosition(position.x, position.y);
                }
            });
        }
    }

    return {
        makeDraggable: makeDraggable,
        absoluteDragStrategy: absoluteDragStrategy,
        relativeDragStrategy: relativeDragStrategy,
    };

};

App.DI.registerSingleton('draggable', ['jQuery'], App.Util.Draggable);
