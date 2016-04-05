'use strict';

const handlebars = require('handlebars');
const misc = require('./misc.js');

handlebars.registerHelper('reltime', function(options) {
    return new handlebars.SafeString(
        '<time datetime="' +
            options.fn(this) +
            '" title="' +
            options.fn(this) +
            '">' +
        misc.formatRelativeTime(options.fn(this)) +
        '</time>');
});
