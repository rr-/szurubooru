var App = App || {};
App.Util = App.Util || {};

App.Util.Resizable = function(jQuery) {
    var KEY_LEFT = 37;
    var KEY_UP = 38;
    var KEY_RIGHT = 39;
    var KEY_DOWN = 40;

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

    function makeResizable($element, enableHotkeys) {
        var $resizer = jQuery('<div class="resizer"></div>');
        var strategy = relativeResizeStrategy($element);
        $element.append($resizer);

        $resizer.mousedown(function(e) {
            e.preventDefault();
            e.stopPropagation();
            $element.focus();
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

        if (enableHotkeys) {
            $element.keydown(function(e) {
                var size = strategy.getSize();
                var oldSize = {width: size.width, height: size.height};
                if (!e.shiftKey) {
                    return;
                }

                var delta = e.ctrlKey ? 10 : 1;
                if (e.which === KEY_LEFT) {
                    size.width -= delta;
                } else if (e.which === KEY_RIGHT) {
                    size.width += delta;
                } else if (e.which === KEY_UP) {
                    size.height -= delta;
                } else if (e.which === KEY_DOWN) {
                    size.height += delta;
                }

                if (size.width !== oldSize.width || size.height !== oldSize.height) {
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    e.preventDefault();
                    strategy.setSize(size.width, size.height);
                }
            });
        }
    }

    return {
        makeResizable: makeResizable,
    };

};

App.DI.registerSingleton('resizable', ['jQuery'], App.Util.Resizable);
