'use strict';

const unindent = require('../util/misc.js').unindent;
const tags = require('../tags.js');
const AutoCompleteControl = require('./auto_complete_control.js');

class TagAutoCompleteControl extends AutoCompleteControl {
    constructor(input, options) {
        const allTags = tags.getNameToTagMap();
        const caseSensitive = false;
        const minLengthForPartialSearch = 3;

        if (!options) {
            options = {};
        }

        options.getMatches = text => {
            const transform = caseSensitive ?
                x => x :
                x => x.toLowerCase();
            const match = text.length < minLengthForPartialSearch ?
                (a, b) => a.startsWith(b) :
                (a, b) => a.includes(b);
            text = transform(text);
            return Array.from(allTags.entries())
                .filter(kv => match(transform(kv[0]), text))
                .sort((kv1, kv2) => {
                    return kv2[1].usages - kv1[1].usages;
                })
                .map(kv => {
                    const category = kv[1].category;
                    const origName = tags.getOriginalTagName(kv[0]);
                    const usages = kv[1].usages;
                    return {
                        caption: unindent`
                            <span class="tag-${category}">
                                ${origName} (${usages})
                            </span>`,
                        value: kv[0],
                    };
                });
        };

        super(input, options);
    }
};

module.exports = TagAutoCompleteControl;
