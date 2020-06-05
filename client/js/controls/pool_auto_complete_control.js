"use strict";

const misc = require("../util/misc.js");
const PoolList = require("../models/pool_list.js");
const AutoCompleteControl = require("./auto_complete_control.js");

function _poolListToMatches(pools, options) {
    return [...pools]
        .sort((pool1, pool2) => {
            return pool2.postCount - pool1.postCount;
        })
        .map((pool) => {
            let cssName = misc.makeCssName(pool.category, "pool");
            const caption =
                '<span class="' +
                cssName +
                '">' +
                misc.escapeHtml(pool.names[0] + " (" + pool.postCount + ")") +
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
                (text.length < minLengthForPartialSearch
                    ? term + "*"
                    : "*" + term + "*") + " sort:post-count";

            return new Promise((resolve, reject) => {
                PoolList.search(query, 0, this._options.maxResults, [
                    "id",
                    "names",
                    "category",
                    "postCount",
                    "version",
                ]).then(
                    (response) =>
                        resolve(
                            _poolListToMatches(response.results, this._options)
                        ),
                    reject
                );
            });
        };

        super(input, options);
    }
}

module.exports = PoolAutoCompleteControl;
