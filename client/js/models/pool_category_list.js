"use strict";

const api = require("../api.js");
const uri = require("../util/uri.js");
const AbstractList = require("./abstract_list.js");
const PoolCategory = require("./pool_category.js");

class PoolCategoryList extends AbstractList {
    constructor() {
        super();
        this._defaultCategory = null;
        this._origDefaultCategory = null;
        this._deletedCategories = [];
        this.addEventListener("remove", (e) => this._evtCategoryDeleted(e));
    }

    static fromResponse(response) {
        const ret = super.fromResponse(response);
        ret._defaultCategory = null;
        for (let poolCategory of ret) {
            if (poolCategory.isDefault) {
                ret._defaultCategory = poolCategory;
            }
        }
        ret._origDefaultCategory = ret._defaultCategory;
        return ret;
    }

    static get() {
        return api
            .get(uri.formatApiLink("pool-categories"))
            .then((response) => {
                return Promise.resolve(
                    Object.assign({}, response, {
                        results: PoolCategoryList.fromResponse(
                            response.results
                        ),
                    })
                );
            });
    }

    get defaultCategory() {
        return this._defaultCategory;
    }

    set defaultCategory(poolCategory) {
        this._defaultCategory = poolCategory;
    }

    save() {
        let promises = [];
        for (let poolCategory of this) {
            promises.push(poolCategory.save());
        }
        for (let poolCategory of this._deletedCategories) {
            promises.push(poolCategory.delete());
        }

        if (this._defaultCategory !== this._origDefaultCategory) {
            promises.push(
                api.put(
                    uri.formatApiLink(
                        "pool-category",
                        this._defaultCategory.name,
                        "default"
                    )
                )
            );
        }

        return Promise.all(promises).then((response) => {
            this._deletedCategories = [];
            return Promise.resolve();
        });
    }

    _evtCategoryDeleted(e) {
        if (!e.detail.poolCategory.isTransient) {
            this._deletedCategories.push(e.detail.poolCategory);
        }
    }
}

PoolCategoryList._itemClass = PoolCategory;
PoolCategoryList._itemName = "poolCategory";

module.exports = PoolCategoryList;
