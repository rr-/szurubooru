'use strict';

const api = require('../api.js');
const uri = require('../util/uri.js');
const AbstractList = require('./abstract_list.js');
const Tag = require('./tag.js');

class TagList extends AbstractList {
    static search(text, page, pageSize, fields) {
        return api.get(
                uri.formatApiLink(
                    'tags', {
                        query: text,
                        page: page,
                        pageSize: pageSize,
                        fields: fields.join(','),
                    }))
            .then(response => {
                return Promise.resolve(Object.assign(
                    {},
                    response,
                    {results: TagList.fromResponse(response.results)}));
            });
    }
}

TagList._itemClass = Tag;
TagList._itemName = 'tag';

module.exports = TagList;
