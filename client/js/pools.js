"use strict";

const misc = require("./util/misc.js");
const PoolCategoryList = require("./models/pool_category_list.js");

let _stylesheet = null;

function refreshCategoryColorMap() {
    return PoolCategoryList.get().then((response) => {
        if (_stylesheet) {
            document.head.removeChild(_stylesheet);
        }
        _stylesheet = document.createElement("style");
        document.head.appendChild(_stylesheet);
        for (let category of response.results) {
            const ruleName = misc.makeCssName(category.name, "pool");
            _stylesheet.sheet.insertRule(
                `.${ruleName} { color: ${category.color} }`,
                _stylesheet.sheet.cssRules.length
            );
        }
    });
}

module.exports = {
    refreshCategoryColorMap: refreshCategoryColorMap,
};
