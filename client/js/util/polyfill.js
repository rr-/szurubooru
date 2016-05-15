'use strict';

require('babel-polyfill');

const keys = Reflect.ownKeys;
const reduce = Function.bind.call(Function.call, Array.prototype.reduce);
const concat = Function.bind.call(Function.call, Array.prototype.concat);
const isEnumerable = Function.bind.call(
    Function.call, Object.prototype.propertyIsEnumerable);

if (!Object.values) {
    Object.values = function values(O) {
        return reduce(keys(O), (v, k) => concat(
            v, typeof k === 'string' && isEnumerable(O, k) ?
                [O[k]] :
                []), []);
    };
}

if (!Object.entries) {
    Object.entries = function entries(O) {
        return reduce(
            keys(O), (e, k) =>
                concat(e, typeof k === 'string' && isEnumerable(O, k) ?
                    [[k, O[k]]] :
                    []), []);
    };
}

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

Promise.prototype.always = function(onResolveOrReject) {
    return this.then(
        onResolveOrReject,
        reason => {
            onResolveOrReject(reason);
            throw reason;
        });
};

if (!String.prototype.format) {
    String.prototype.format = function() {
        let str = this.toString();
        if (!arguments.length) {
            return str;
        }
        const type = typeof arguments[0];
        const args = (type == 'string' || type == 'number') ?
            arguments : arguments[0];
        for (let arg in args) {
            str = str.replace(
                new RegExp('\\{' + arg + '\\}', 'gi'),
                () => { return args[arg]; });
        }
        return str;
    };
}

Number.prototype.between = function(a, b, inclusive) {
    const min = Math.min(a, b);
    const max = Math.max(a, b);
    return inclusive ?
        this >= min && this <= max :
        this > min && this < max;
};
