'use strict';

const request = require('superagent');

let _tags = null;
let _categories = null;
let _stylesheet = null;

function getTagByName(name) {
    return _tags.get(name.toLowerCase());
}

function getCategoryByName(name) {
    return _categories.get(name.toLowerCase());
}

function getNameToTagMap() {
    return _tags;
}

function getAllTags() {
    return _tags.values();
}

function getAllCategories() {
    return _categories.values();
}

function getOriginalTagName(name) {
    const actualTag = getTagByName(name);
    if (actualTag) {
        for (let originalName of actualTag.names) {
            if (originalName.toLowerCase() == name.toLowerCase()) {
                return originalName;
            }
        }
    }
    return name;
}

function _tagsToMap(tags) {
    let map = new Map();
    for (let tag of tags) {
        for (let name of tag.names) {
            map.set(name.toLowerCase(), tag);
        }
    }
    return map;
}

function _tagCategoriesToMap(categories) {
    let map = new Map();
    for (let category of categories) {
        map.set(category.name.toLowerCase(), category);
    }
    return map;
}

function _refreshStylesheet() {
    if (_stylesheet) {
        document.head.removeChild(_stylesheet);
    }
    _stylesheet = document.createElement('style');
    document.head.appendChild(_stylesheet);
    for (let category of getAllCategories()) {
        _stylesheet.sheet.insertRule(
            `.tag-${category.name} { color: ${category.color} }`,
            _stylesheet.sheet.cssRules.length);
    }
}

function refreshExport() {
    return new Promise((resolve, reject) => {
        request.get('/data/tags.json').end((error, response) => {
            if (error) {
                _tags = new Map();
                _categories = new Map();
                reject(error);
            }
            _tags = _tagsToMap(
                response.body ? response.body.tags : []);
            _categories = _tagCategoriesToMap(
                response.body ? response.body.categories : []);
            _refreshStylesheet();
            resolve();
        });
    });
}

module.exports = {
    getAllCategories: getAllCategories,
    getAllTags: getAllTags,
    getTagByName: getTagByName,
    getCategoryByName: getCategoryByName,
    getNameToTagMap: getNameToTagMap,
    getOriginalTagName: getOriginalTagName,
    refreshExport: refreshExport,
};
