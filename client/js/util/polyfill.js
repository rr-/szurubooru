'use strict';

// fix iterating over NodeList in Chrome and Opera
NodeList.prototype[Symbol.iterator] = Array.prototype[Symbol.iterator];

// non standard
Node.prototype.prependChild = function(child) {
    if (this.firstChild) {
        this.insertBefore(child, this.firstChild);
    } else {
        this.appendChild(child);
    }
};

// non standard
Promise.prototype.always = function(onResolveOrReject) {
    return this.then(
        onResolveOrReject,
        reason => {
            onResolveOrReject(reason);
            throw reason;
        });
};

// non standard
Number.prototype.between = function(a, b, inclusive) {
    const min = Math.min(a, b);
    const max = Math.max(a, b);
    return inclusive ?
        this >= min && this <= max :
        this > min && this < max;
};
