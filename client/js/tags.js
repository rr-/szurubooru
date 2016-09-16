'use strict';

const misc = require('./util/misc.js');
const request = require('superagent');

let _tags = new Map();
let _categories = new Map();
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
        const ruleName = misc.makeCssName(category.name, 'tag');
        _stylesheet.sheet.insertRule(
            `.${ruleName} { color: ${category.color} }`,
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
                return;
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

function getSuggestions(tagName) {
    const actualTag = getTagByName(tagName) || {};
    return actualTag.suggestions || [];
}

module.exports = misc.arrayToObject([
    getAllCategories,
    getAllTags,
    getTagByName,
    getCategoryByName,
    getNameToTagMap,
    getOriginalTagName,
    refreshExport,
    getAllImplications,
    getSuggestions,
], func => func.name);
