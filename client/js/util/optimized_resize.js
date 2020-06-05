"use strict";

let callbacks = [];
let running = false;

function resize() {
    if (!running) {
        running = true;
        if (window.requestAnimationFrame) {
            window.requestAnimationFrame(runCallbacks);
        } else {
            setTimeout(runCallbacks, 66);
        }
    }
}

function runCallbacks() {
    callbacks.forEach((callback) => {
        callback();
    });
    running = false;
}

function add(callback) {
    callbacks.push(callback);
}

function remove(callback) {
    callbacks = callbacks.filter((c) => c !== callback);
}

window.addEventListener("resize", resize);
module.exports = { add: add, remove: remove };
