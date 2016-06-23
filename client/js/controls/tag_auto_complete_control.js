'use strict';

const tags = require('../tags.js');
const misc = require('../util/misc.js');
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
                    const origName = misc.escapeHtml(
                        tags.getOriginalTagName(kv[0]));
                    const category = kv[1].category;
                    const usages = kv[1].usages;
                    const cssName = misc.makeCssName(category, 'tag');
                    return {
                        caption: misc.unindent`
                            <span class="${cssName}">
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
