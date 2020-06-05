"use strict";

const api = require("../api.js");
const uri = require("../util/uri.js");
const AbstractList = require("./abstract_list.js");
const Pool = require("./pool.js");

class PoolList extends AbstractList {
    static search(text, offset, limit, fields) {
        return api
            .get(
                uri.formatApiLink("pools", {
                    query: text,
                    offset: offset,
                    limit: limit,
                    fields: fields.join(","),
                })
            )
            .then((response) => {
                return Promise.resolve(
                    Object.assign({}, response, {
                        results: PoolList.fromResponse(response.results),
                    })
                );
            });
    }

    hasPoolId(poolId) {
        for (let pool of this._list) {
            if (pool.id === poolId) {
                return true;
            }
        }
        return false;
    }

    removeById(poolId) {
        for (let pool of this._list) {
            if (pool.id === poolId) {
                this.remove(pool);
            }
        }
    }
}

PoolList._itemClass = Pool;
PoolList._itemName = "pool";

module.exports = PoolList;
