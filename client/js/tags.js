'use strict';

const request = require('superagent');
const util = require('./util/misc.js');
const events = require('./events.js');

let _export = null;
let _stylesheet = null;

function _tagsToDictionary(tags) {
    let dict = {};
    for (let tag of tags) {
        for (let name of tag.names) {
            dict[name] = tag;
        }
    }
    return dict;
}

function _tagCategoriesToDictionary(categories) {
    let dict = {};
    for (let category of categories) {
        dict[category.name] = category;
    }
    return dict;
}

function _refreshStylesheet() {
    if (_stylesheet) {
        document.head.removeChild(_stylesheet);
    }
    _stylesheet = document.createElement('style');
    document.head.appendChild(_stylesheet);
    for (let category of Object.values(_export.categories)) {
        _stylesheet.sheet.insertRule(
            '.tag-{0} { color: {1} }'.format(category.name, category.color),
            _stylesheet.sheet.cssRules.length);
    }
}

function refreshExport() {
    return new Promise((resolve, reject) => {
        request.get('/data/tags.json').end((error, response) => {
            if (error) {
                console.log('Error while fetching exported tags', error);
                _export = {tags: {}, categories: {}};
                reject(error);
            }
            _export = response.body;
            _export.tags = _tagsToDictionary(_export.tags);
            _export.categories = _tagCategoriesToDictionary(
                _export.categories);
            _refreshStylesheet();
            resolve();
        });
    });
}

function getExport() {
    return _export || {};
}

events.listen(
    events.TagsChange,
    () => { refreshExport(); return true; });

module.exports = {
    getExport: getExport,
    refreshExport: refreshExport,
};
