"use strict";

const misc = require("../util/misc.js");
const PoolList = require("../models/pool_list.js");
const AutoCompleteControl = require("./auto_complete_control.js");

function _poolListToMatches(text, pools, options) {
    return [...pools]
        .sort((pool1, pool2) => {
            return pool2.postCount - pool1.postCount;
        })
        .map((pool) => {
            pool.matchingNames = misc.matchingNames(text, pool.names);
            let cssName = misc.makeCssName(pool.category, "pool");
            const caption =
                '<span class="' +
                cssName +
                '">' +
                misc.escapeHtml(pool.matchingNames[0] + " (" + pool.postCount + ")") +
                "</span>";
            return {
                caption: caption,
                value: pool,
            };
        });
}

class PoolAutoCompleteControl extends AutoCompleteControl {
    constructor(input, options) {
        const minLengthForPartialSearch = 3;

        options.getMatches = (text) => {
            const term = misc.escapeSearchTerm(text);
            const query =
                (text.length >= minLengthForPartialSearch
                    ? "*" + term + "*"
                    : term + "*") + " sort:post-count";

            return new Promise((resolve, reject) => {
                PoolList.search(query, 0, this._options.maxResults, [
                    "id",
                    "names",
                    "category",
                    "postCount",
                    "version",
                ],
                { noProgress: true }).then(
                    (response) =>
                        resolve(
                            _poolListToMatches(text, response.results, this._options)
                        ),
                    reject
                );
            });
        };

        super(input, options);
    }

    _getActiveSuggestion() {
        if (this._activeResult === -1) {
            return null;
        }
        const result = this._results[this._activeResult].value;
        const textToFind = this._options.getTextToFind();
        result.matchingNames = misc.matchingNames(textToFind, result.names);
        return result;
    }
}

module.exports = PoolAutoCompleteControl;
