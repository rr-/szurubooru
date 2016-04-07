'use strict';

// fix iterating over NodeList in Chrome and Opera
NodeList.prototype[Symbol.iterator] = Array.prototype[Symbol.iterator];
