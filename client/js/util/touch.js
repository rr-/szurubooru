"use strict";

const direction = {
    NONE: null,
    LEFT: "left",
    RIGHT: "right",
    DOWN: "down",
    UP: "up",
};

function handleTouchStart(handler, evt) {
    const touchEvent = evt.touches[0];
    handler._xStart = touchEvent.clientX;
    handler._yStart = touchEvent.clientY;
    handler._startScrollY = window.scrollY;
}

function handleTouchMove(handler, evt) {
    if (!handler._xStart || !handler._yStart) {
        return;
    }

    const xDirection = handler._xStart - evt.touches[0].clientX;
    const yDirection = handler._yStart - evt.touches[0].clientY;

    if (Math.abs(xDirection) > Math.abs(yDirection)) {
        if (xDirection > 0) {
            handler._direction = direction.LEFT;
        } else {
            handler._direction = direction.RIGHT;
        }
    } else if (yDirection > 0) {
        handler._direction = direction.DOWN;
    } else {
        handler._direction = direction.UP;
    }
}

function handleTouchEnd(handler, evt) {
    evt.startScrollY = handler._startScrollY;
    switch (handler._direction) {
        case direction.NONE:
            return;
        case direction.LEFT:
            handler._swipeLeftTask(evt);
            break;
        case direction.RIGHT:
            handler._swipeRightTask(evt);
            break;
        case direction.DOWN:
            handler._swipeDownTask(evt);
            break;
        case direction.UP:
            handler._swipeUpTask(evt);
    }

    handler._xStart = null;
    handler._yStart = null;
}

class Touch {
    constructor(
        target,
        swipeLeft = () => {},
        swipeRight = () => {},
        swipeUp = () => {},
        swipeDown = () => {}
    ) {
        this._target = target;

        this._swipeLeftTask = swipeLeft;
        this._swipeRightTask = swipeRight;
        this._swipeUpTask = swipeUp;
        this._swipeDownTask = swipeDown;

        this._xStart = null;
        this._yStart = null;
        this._direction = direction.NONE;

        this._target.addEventListener('touchstart', (evt) => 
            { handleTouchStart(this, evt); }
        );
        this._target.addEventListener('touchmove', (evt) =>
            { handleTouchMove(this, evt); }
        );
        this._target.addEventListener('touchend', (evt) =>
            { handleTouchEnd(this, evt); }
        );
    }
}

module.exports = Touch;
