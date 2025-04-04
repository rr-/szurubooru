"use strict";

const misc = require("../util/misc.js");
const views = require("../util/views.js");
const TagList = require("../models/tag_list.js");
const AutoCompleteControl = require("./auto_complete_control.js");

function _tagListToMatches(tags, options, negated) {
    return [...tags]
        .sort((tag1, tag2) => {
            return tag2.usages - tag1.usages;
        })
        .map((tag) => {
            let cssName = misc.makeCssName(tag.category, "tag");
            if (options.isTaggedWith(tag.names[0])) {
                cssName += " disabled";
            }
            if (negated) {
                tag.names = tag.names.map((tagName) => "-"+tagName);
            }
            const caption =
                '<span class="' +
                cssName +
                '">' +
                misc.escapeHtml(tag.names[0] + " (" + tag.postCount + ")") +
                "</span>";
            return {
                caption: caption,
                value: tag,
            };
        });
}

class TagAutoCompleteControl extends AutoCompleteControl {
    constructor(input, options) {
        const minLengthForPartialSearch = 3;

        options = Object.assign(
            {
                isTaggedWith: (tag) => false,
                isNegationAllowed: false,
            },
            options
        );

        options.getMatches = (text) => {
            const negated = options.isNegationAllowed && text[0] === "-";
            if (negated) text = text.substring(1);
            if (!text) {
                return new Promise((resolve, reject) => {
                    (response) => resolve(null),
                    reject
                });
            }

            const term = misc.escapeSearchTerm(text);
            const query =
                (text.length >= minLengthForPartialSearch || (!options.isNegationAllowed && text[0] === "-")
                    ? "*" + term + "*"
                    : term + "*") + " sort:usages";

            return new Promise((resolve, reject) => {
                TagList.search(query, 0, this._options.maxResults, [
                    "names",
                    "category",
                    "usages",
                ]).then(
                    (response) =>
                        resolve(
                            _tagListToMatches(response.results, this._options, negated)
                        ),
                    reject
                );
            });
        };

        super(input, options);
    }
}

module.exports = TagAutoCompleteControl;
