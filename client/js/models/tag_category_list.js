"use strict";

const api = require("../api.js");
const uri = require("../util/uri.js");
const AbstractList = require("./abstract_list.js");
const TagCategory = require("./tag_category.js");

class TagCategoryList extends AbstractList {
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
        for (let tagCategory of ret) {
            if (tagCategory.isDefault) {
                ret._defaultCategory = tagCategory;
            }
        }
        ret._origDefaultCategory = ret._defaultCategory;
        return ret;
    }

    static get() {
        return api
            .get(uri.formatApiLink("tag-categories"))
            .then((response) => {
                return Promise.resolve(
                    Object.assign({}, response, {
                        results: TagCategoryList.fromResponse(
                            response.results
                        ),
                    })
                );
            });
    }

    get defaultCategory() {
        return this._defaultCategory;
    }

    set defaultCategory(tagCategory) {
        this._defaultCategory = tagCategory;
    }

    save() {
        let promises = [];
        for (let tagCategory of this) {
            promises.push(tagCategory.save());
        }
        for (let tagCategory of this._deletedCategories) {
            promises.push(tagCategory.delete());
        }

        if (this._defaultCategory !== this._origDefaultCategory) {
            promises.push(
                api.put(
                    uri.formatApiLink(
                        "tag-category",
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
        if (!e.detail.tagCategory.isTransient) {
            this._deletedCategories.push(e.detail.tagCategory);
        }
    }
}

TagCategoryList._itemClass = TagCategory;
TagCategoryList._itemName = "tagCategory";

module.exports = TagCategoryList;
