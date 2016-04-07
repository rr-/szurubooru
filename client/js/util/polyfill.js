'use strict';

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
