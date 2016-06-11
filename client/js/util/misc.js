'use strict';

const marked = require('marked');

function* range(start=0, end=null, step=1) {
    if (end == null) {
        end = start;
        start = 0;
    }

    for (let i = start; i < end; i += step) {
        yield i;
    }
}

function _formatUnits(number, base, suffixes, callback) {
    if (!number && number !== 0) {
        return NaN;
    }
    number *= 1.0;
    let suffix = suffixes.shift();
    while (number >= base && suffixes.length > 0) {
        suffix = suffixes.shift();
        number /= base;
    }
    if (callback === undefined) {
        callback = (number, suffix) => {
            return suffix ? number.toFixed(1) + suffix : number;
        };
    }
    return callback(number, suffix);
}

function formatFileSize(fileSize) {
    return _formatUnits(
        fileSize,
        1024,
        ['B', 'K', 'M', 'G'],
        (number, suffix) => {
            const decimalPlaces = number < 20 && suffix !== 'B' ? 1 : 0;
            return number.toFixed(decimalPlaces) + suffix;
        });
}

function formatRelativeTime(timeString) {
    if (!timeString) {
        return 'never';
    }

    const then = Date.parse(timeString);
    const now = Date.now();
    const difference = Math.abs(now - then) / 1000.0;
    const future = now < then;

    const descriptions = [
        [60,                            'a few seconds', null],
        [60 * 2,                        'a minute',      null],
        [60 * 60,                       '% minutes',     60],
        [60 * 60 * 2,                   'an hour',       null],
        [60 * 60 * 24,                  '% hours',       60 * 60],
        [60 * 60 * 24 * 2,              'a day',         null],
        [60 * 60 * 24 * 30.42,          '% days',        60 * 60 * 24],
        [60 * 60 * 24 * 30.42 * 2,      'a month',       null],
        [60 * 60 * 24 * 30.42 * 12,     '% months',      60 * 60 * 24 * 30.42],
        [60 * 60 * 24 * 30.42 * 12 * 2, 'a year',        null],
        [8640000000000000 /*max*/, '% years', 60 * 60 * 24 * 30.42 * 12],
    ];

    let text = null;
    for (let kv of descriptions) {
        const multiplier = kv[0];
        const template = kv[1];
        const divider = kv[2];
        if (difference < multiplier) {
            text = template.replace(/%/, Math.round(difference / divider));
            break;
        }
    }

    if (text === 'a day') {
        return future ? 'tomorrow' : 'yesterday';
    }
    return future ? 'in ' + text : text + ' ago';
}

function formatMarkdown(text) {
    const renderer = new marked.Renderer();

    const options = {
        renderer: renderer,
        breaks: true,
        sanitize: true,
        smartypants: true,
    };

    const sjis = [];

    const preDecorator = text => {
        text = text.replace(
            /\[sjis\]((?:[^\[]|\[(?!\/?sjis\]))+)\[\/sjis\]/ig,
            (match, capture) => {
                var ret = '%%%SJIS' + sjis.length;
                sjis.push(capture);
                return ret;
            });
        //prevent ^#... from being treated as headers, due to tag permalinks
        text = text.replace(/^#/g, '%%%#');
        //fix \ before ~ being stripped away
        text = text.replace(/\\~/g, '%%%T');
        //post, user and tags premalinks
        text = text.replace(
            /(^|^\(|(?:[^\]])\(|[\s<>\[\]\)])([+#@][a-zA-Z0-9_-]+)/g,
            '$1[$2]($2)');
        text = text.replace(/\]\(@(\d+)\)/g, '](#/post/$1)');
        text = text.replace(/\]\(\+([a-zA-Z0-9_-]+)\)/g, '](#/user/$1)');
        text = text.replace(/\]\(#([a-zA-Z0-9_-]+)\)/g, '](#/posts/query=$1)');
        return text;
    };

    const postDecorator = text => {
        //restore fixes
        text = text.replace(/%%%T/g, '\\~');
        text = text.replace(/%%%#/g, '#');

        text = text.replace(
            /(?:<p>)?%%%SJIS(\d+)(?:<\/p>)?/,
            (match, capture) => {
                return '<div class="sjis">' + sjis[capture] + '</div>';
            });

        //search permalinks
        text = text.replace(
            /\[search\]((?:[^\[]|\[(?!\/?search\]))+)\[\/search\]/ig,
            '<a href="#/posts/query=$1"><code>$1</code></a>');
        //spoilers
        text = text.replace(
            /\[spoiler\]((?:[^\[]|\[(?!\/?spoiler\]))+)\[\/spoiler\]/ig,
            '<span class="spoiler">$1</span>');
        //[small]
        text = text.replace(
            /\[small\]((?:[^\[]|\[(?!\/?small\]))+)\[\/small\]/ig,
            '<small>$1</small>');
        //strike-through
        text = text.replace(/(^|[^\\])(~~|~)([^~]+)\2/g, '$1<del>$3</del>');
        text = text.replace(/\\~/g, '~');
        return text;
    };

    return postDecorator(marked(preDecorator(text), options));
}

function formatSearchQuery(dict) {
    let result = [];
    for (let key of Object.keys(dict)) {
        const value = dict[key];
        if (value) {
            result.push(`${key}=${value}`);
        }
    }
    return result.join(';');
}

function parseSearchQuery(query) {
    let result = {};
    for (let word of (query || '').split(/;/)) {
        const [key, value] = word.split(/=/, 2);
        result[key] = value;
    }
    result.text = result.text || '';
    result.page = parseInt(result.page || '1');
    return result;
}

function parseSearchQueryRoute(ctx, next) {
    ctx.searchQuery = parseSearchQuery(ctx.params.query || '');
    next();
}

function unindent(callSite, ...args) {
    function format(str) {
        let size = -1;
        return str.replace(/\n(\s+)/g, (m, m1) => {
            if (size < 0) {
                size = m1.replace(/\t/g, '    ').length;
            }
            return "\n" + m1.slice(Math.min(m1.length, size));
        });
    }
    if (typeof callSite === 'string') {
        return format(callSite);
    }
    if (typeof callSite === 'function') {
        return (...args) => format(callSite(...args));
    }
    let output = callSite
        .slice(0, args.length + 1)
        .map((text, i) => (i === 0 ? '' : args[i - 1]) + text)
        .join('');
    return format(output);
}

module.exports = {
    range: range,
    formatSearchQuery: formatSearchQuery,
    parseSearchQuery: parseSearchQuery,
    parseSearchQueryRoute: parseSearchQueryRoute,
    formatRelativeTime: formatRelativeTime,
    formatFileSize: formatFileSize,
    formatMarkdown: formatMarkdown,
    unindent: unindent,
};
