/* eslint-disable func-names, no-extend-native */

"use strict";

// fix iterating over NodeList in Chrome and Opera
NodeList.prototype[Symbol.iterator] = Array.prototype[Symbol.iterator];

NodeList.prototype.querySelector = function (...args) {
    for (let node of this) {
        if (node.nodeType === 3) {
            continue;
        }
        const result = node.querySelector(...args);
        if (result) {
            return result;
        }
    }
    return null;
};

NodeList.prototype.querySelectorAll = function (...args) {
    let result = [];
    for (let node of this) {
        if (node.nodeType === 3) {
            continue;
        }
        for (let childNode of node.querySelectorAll(...args)) {
            result.push(childNode);
        }
    }
    return result;
};

// non standard
Node.prototype.prependChild = function (child) {
    if (this.firstChild) {
        this.insertBefore(child, this.firstChild);
    } else {
        this.appendChild(child);
    }
};

// non standard
Promise.prototype.always = function (onResolveOrReject) {
    return this.then(onResolveOrReject, (reason) => {
        onResolveOrReject(reason);
        throw reason;
    });
};

// non standard
Number.prototype.between = function (a, b, inclusive) {
    const min = Math.min(a, b);
    const max = Math.max(a, b);
    return inclusive ? this >= min && this <= max : this > min && this < max;
};

// non standard
Promise.prototype.abort = () => {};

// non standard
Date.prototype.addDays = function (days) {
    let dat = new Date(this.valueOf());
    dat.setDate(dat.getDate() + days);
    return dat;
};
