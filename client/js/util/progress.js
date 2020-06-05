"use strict";

const nprogress = require("nprogress");

let nesting = 0;

function start() {
    if (!nesting) {
        nprogress.start();
    }
    nesting++;
}

function done() {
    nesting--;
    if (nesting > 0) {
        nprogress.inc();
    } else {
        nprogress.done();
    }
}

module.exports = {
    start: start,
    done: done,
};
