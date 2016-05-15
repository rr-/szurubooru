'use strict';

const lodash = require('lodash');
const tags = require('../tags.js');
const AutoCompleteControl = require('./auto_complete_control.js');

class TagAutoCompleteControl extends AutoCompleteControl {
    constructor(input, options) {
        const allTags = tags.getExport().tags;
        const caseSensitive = false;
        const minLengthForPartialSearch = 3;

        if (!options) {
            options = {};
        }

        options.getMatches = text => {
            const regex = new RegExp(
                text.length < minLengthForPartialSearch ?
                    '^' + lodash.escapeRegExp(text) :
                    lodash.escapeRegExp(text),
                caseSensitive ? '' : 'i');
            return Array.from(allTags.entries())
                .filter(kv => kv[0].match(regex))
                .sort((kv1, kv2) => {
                    return kv2[1].usages - kv1[1].usages;
                })
                .map(kv => {
                    return {
                        caption:
                            '<span class="tag-{0}">{1} ({2})</span>'.format(
                                kv[1].category,
                                kv[0],
                                kv[1].usages),
                        value: kv[0],
                    };
                });
        };

        super(input, options);
    }
};

module.exports = TagAutoCompleteControl;
