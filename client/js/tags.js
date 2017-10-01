'use strict';

const misc = require('./util/misc.js');
const TagCategoryList = require('./models/tag_category_list.js');

let _stylesheet = null;

function refreshCategoryColorMap() {
    return TagCategoryList.get().then(response => {
        if (_stylesheet) {
            document.head.removeChild(_stylesheet);
        }
        _stylesheet = document.createElement('style');
        document.head.appendChild(_stylesheet);
        for (let category of response.results) {
            const ruleName = misc.makeCssName(category.name, 'tag');
            _stylesheet.sheet.insertRule(
                `.${ruleName} { color: ${category.color} }`,
                _stylesheet.sheet.cssRules.length);
        }
    });
}

function getAllImplications(tagName) {
    let implications = [];
    let check = [tagName];
    while (check.length) {
        let tagName = check.pop();
        const actualTag = getTagByName(tagName) || {};
        for (let implication of actualTag.implications || []) {
            if (implications.includes(implication)) {
                continue;
            }
            implications.push(implication);
            check.push(implication);
        }
    }
    return Array.from(implications);
}

module.exports = {
    refreshCategoryColorMap: refreshCategoryColorMap,
    getAllImplications:      getAllImplications,
};
