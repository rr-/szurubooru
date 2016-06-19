'use strict';

const api = require('../api.js');
const AbstractList = require('./abstract_list.js');
const Tag = require('./tag.js');

class TagList extends AbstractList {
    static search(text, page, pageSize, fields) {
        const url =
            `/tags/?query=${text}` +
            `&page=${page}` +
            `&pageSize=${pageSize}` +
            `&fields=${fields.join(',')}`;
        return api.get(url).then(response => {
            response.results = TagList.fromResponse(response.results);
            return Promise.resolve(response);
        });
    }
}

TagList._itemClass = Tag;
TagList._itemName = 'tag';

module.exports = TagList;
