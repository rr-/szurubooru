'use strict';

const api = require('../api.js');
const uri = require('../util/uri.js');
const AbstractList = require('./abstract_list.js');
const Snapshot = require('./snapshot.js');

class SnapshotList extends AbstractList {
    static search(text, page, pageSize) {
        return api.get(uri.formatApiLink(
                'snapshots', {query: text, page: page, pageSize: pageSize}))
            .then(response => {
                return Promise.resolve(Object.assign(
                    {},
                    response,
                    {results: SnapshotList.fromResponse(response.results)}));
            });
    }
}

SnapshotList._itemClass = Snapshot;
SnapshotList._itemName = 'snapshot';

module.exports = SnapshotList;
