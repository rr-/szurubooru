'use strict';

// modified page.js by visionmedia
// - removed unused crap
// - refactored to classes
// - simplified method chains
// - added ability to call .save() in .exit() without side effects
// - page refresh recovers state from history

const pathToRegexp = require('path-to-regexp');
const clickEvent = document.ontouchstart ? 'touchstart' : 'click';
let location = window.history.location || window.location;

const base = '';

function _decodeURLEncodedURIComponent(val) {
    if (typeof val !== 'string') {
        return val;
    }
    return decodeURIComponent(val.replace(/\+/g, ' '));
}

function _isSameOrigin(href) {
    let origin = location.protocol + '//' + location.hostname;
    if (location.port) {
        origin += ':' + location.port;
    }
    return href && href.indexOf(origin) === 0;
}

class Context {
    constructor(path, state) {
        if (path[0] === '/' && path.indexOf(base) !== 0) {
            path = base + path;
        }

        this.canonicalPath = path;
        this.path = path.replace(base, '') || '/';

        this.title = document.title;
        this.state = state || {};
        this.state.path = path;
        this.parameters = {};
    }

    pushState() {
        history.pushState(this.state, this.title, this.canonicalPath);
    }

    save() {
        history.replaceState(this.state, this.title, this.canonicalPath);
    }
};

class Route {
    constructor(path, options) {
        options = options || {};
        this.path = (path === '*') ? '(.*)' : path;
        this.method = 'GET';
        this.regexp = pathToRegexp(this.path, this.keys = [], options);
    }

    middleware(fn) {
        return (ctx, next) => {
            if (this.match(ctx.path, ctx.parameters)) {
                return fn(ctx, next);
            }
            next();
        };
    }

    match(path, parameters) {
        const keys = this.keys;
        const qsIndex = path.indexOf('?');
        const pathname = ~qsIndex ? path.slice(0, qsIndex) : path;
        const m = this.regexp.exec(decodeURIComponent(pathname));

        if (!m) {
            return false;
        }

        for (let i = 1, len = m.length; i < len; ++i) {
            const key = keys[i - 1];
            const val = _decodeURLEncodedURIComponent(m[i]);
            if (val !== undefined ||
                    !(hasOwnProperty.call(parameters, key.name))) {
                parameters[key.name] = val;
            }
        }

        return true;
    }
};

class Router {
    constructor() {
        this._callbacks = [];
        this._exits = [];
    }

    enter(path) {
        const route = new Route(path);
        for (let i = 1; i < arguments.length; ++i) {
            this._callbacks.push(route.middleware(arguments[i]));
        }
    }

    exit(path, fn) {
        const route = new Route(path);
        for (let i = 1; i < arguments.length; ++i) {
            this._exits.push(route.middleware(arguments[i]));
        }
    }

    start() {
        if (this._running) {
            return;
        }
        this._running = true;
        this._onPopState = _onPopState(this);
        this._onClick = _onClick(this);
        window.addEventListener('popstate', this._onPopState, false);
        document.addEventListener(clickEvent, this._onClick, false);
        const url = location.pathname + location.search + location.hash;
        return this.replace(url, history.state, true);
    }

    stop() {
        if (!this._running) {
            return;
        }
        this._running = false;
        document.removeEventListener(clickEvent, this._onClick, false);
        window.removeEventListener('popstate', this._onPopState, false);
    }

    show(path, state, push) {
        const oldPath = this.ctx ? this.ctx.path : ctx.path;
        const ctx = new Context(path, state);
        this.dispatch(ctx, () => {
            if (ctx.path !== oldPath && push !== false) {
                ctx.pushState();
            }
        });
        return ctx;
    }

    replace(path, state, dispatch) {
        var ctx = new Context(path, state);
        if (dispatch) {
            this.dispatch(ctx, () => {
                ctx.save();
            });
        } else {
            ctx.save();
        }
        return ctx;
    }

    dispatch(ctx, middle) {
        const swap = (_ctx, next) => {
            this.ctx = ctx;
            middle();
            next();
        };
        const callChain = (this.ctx ? this._exits : [])
            .concat(
                [swap],
                this._callbacks,
                [this._unhandled, (ctx, next) => {}]);

        let i = 0;
        let fn = () => {
            callChain[i++](this.ctx, fn);
        };
        fn();
    }

    _unhandled(ctx, next) {
        let current = location.pathname + location.search;
        if (current === ctx.canonicalPath) {
            return;
        }
        router.stop();
        location.href = ctx.canonicalPath;
    }
};

const _onPopState = router => {
    let loaded = false;
    if (document.readyState === 'complete') {
        loaded = true;
    } else {
        window.addEventListener(
            'load',
            () => {
                setTimeout(() => {
                    loaded = true;
                }, 0);
            });
    }
    return e => {
        if (!loaded) {
            return;
        }
        if (e.state) {
            const path = e.state.path;
            router.replace(path, e.state, true);
        } else {
            router.show(
                location.pathname + location.hash,
                undefined,
                false);
        }
    };
};

const _onClick = router => {
    return e => {
        if (1 !== _which(e)) {
            return;
        }
        if (e.metaKey || e.ctrlKey || e.shiftKey) {
            return;
        }
        if (e.defaultPrevented) {
            return;
        }

        let el = e.path ? e.path[0] : e.target;
        while (el && el.nodeName !== 'A') {
            el = el.parentNode;
        }
        if (!el || el.nodeName !== 'A') {
            return;
        }

        if (el.hasAttribute('download') ||
                el.getAttribute('rel') === 'external') {
            return;
        }

        const link = el.getAttribute('href');
        if (el.pathname === location.pathname && (el.hash || '#' === link)) {
            return;
        }
        if (link && link.indexOf('mailto:') > -1) {
            return;
        }
        if (el.target) {
            return;
        }
        if (!_isSameOrigin(el.href)) {
            return;
        }

        let path = el.pathname + el.search + (el.hash || '');

        const orig = path;
        if (path.indexOf(base) === 0) {
            path = path.substr(base.length);
        }
        if (base && orig === path) {
            return;
        }
        e.preventDefault();
        router.show(orig);
    };
};

function _which(e) {
    e = e || window.event;
    return e.which === null ? e.button : e.which;
}

Router.prototype.Context = Context;
Router.prototype.Route = Route;
module.exports = new Router();
