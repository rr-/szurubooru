'use strict';

const handlebars = require('handlebars');
const misc = require('./misc.js');

handlebars.registerHelper('reltime', function(time) {
    return new handlebars.SafeString(
        '<time datetime="' + time + '" title="' + time + '">' +
        misc.formatRelativeTime(time) +
        '</time>');
});

handlebars.registerHelper('toLowerCase', function(str) {
    return str.toLowerCase();
});
