'use strict';

const views = require('../util/views.js');
const handlebars = require('handlebars');
const misc = require('./misc.js');

handlebars.registerHelper('reltime', function(time) {
    return new handlebars.SafeString(
        views.makeNonVoidElement(
            'time',
            {datetime: time, title: time},
            misc.formatRelativeTime(time)));
});

handlebars.registerHelper('thumbnail', function(url) {
    return new handlebars.SafeString(
        views.makeNonVoidElement('span', {
            class: 'thumbnail',
            style: 'background-image: url(\'{0}\')'.format(url)
        }, views.makeVoidElement('img', {alt: 'thumbnail', src: url})));
});

handlebars.registerHelper('toLowerCase', function(str) {
    return str.toLowerCase();
});

handlebars.registerHelper('radio', function(options) {
    return new handlebars.SafeString('{0}{1}'.format(
        views.makeVoidElement('input', {
            id: options.hash.id,
            name: options.hash.name,
            value: options.hash.value,
            type: 'radio',
            checked: options.hash.selectedValue === options.hash.value,
            required: options.hash.required,
        }),
        views.makeNonVoidElement('label', {
            for: options.hash.id,
            class: 'radio',
        }, options.hash.text)));
});

handlebars.registerHelper('checkbox', function(options) {
    return new handlebars.SafeString('{0}{1}'.format(
        views.makeVoidElement('input', {
            id: options.hash.id,
            name: options.hash.name,
            value: options.hash.value,
            type: 'checkbox',
            required: options.hash.required,
        }),
        views.makeNonVoidElement('label', {
            for: options.hash.id,
            class: 'checkbox',
        }, options.hash.text)));
});

handlebars.registerHelper('select', function(options) {
    return new handlebars.SafeString('{0}{1}'.format(
        views.makeNonVoidElement(
            'label', {for: options.hash.id}, options.hash.text),
        views.makeNonVoidElement(
            'select',
            {id: options.hash.id, name: options.hash.name},
            Object.keys(options.hash.keyValues).map(key => {
                return views.makeNonVoidElement(
                    'option',
                    {value: key, selected: key === options.hash.selectedKey},
                    options.hash.keyValues[key]);
            }).join(''))));
});

handlebars.registerHelper('input', function(options) {
    return new handlebars.SafeString('{0}{1}'.format(
        views.makeNonVoidElement(
            'label', {for: options.hash.id}, options.hash.text),
        views.makeVoidElement(
            'input', {
                type: options.hash.inputType,
                name: options.hash.name,
                id: options.hash.id,
                value: options.hash.value || '',
                required: options.hash.required,
                pattern: options.hash.pattern,
                placeholder: options.hash.placeholder,
            })));
});

handlebars.registerHelper('textInput', function(options) {
    options.hash.inputType = 'text';
    return handlebars.helpers.input(options);
});

handlebars.registerHelper('passwordInput', function(options) {
    options.hash.inputType = 'password';
    return handlebars.helpers.input(options);
});

handlebars.registerHelper('emailInput', function(options) {
    options.hash.inputType = 'email';
    return handlebars.helpers.input(options);
});

handlebars.registerHelper('alignFlexbox', function(options) {
    return new handlebars.SafeString(
        Array.from(misc.range(20))
            .map(() => '<li class="flexbox-dummy"></li>').join(''));
});
