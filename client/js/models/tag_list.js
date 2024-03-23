"use strict";

const api = require("../api.js");
const uri = require("../util/uri.js");
const AbstractList = require("./abstract_list.js");
const Tag = require("./tag.js");

class TagList extends AbstractList {
    static search(text, offset, limit, fields, options) {
        return api
            .get(
                uri.formatApiLink("tags", {
                    query: text,
                    offset: offset,
                    limit: limit,
                    fields: fields.join(","),
                }),
                options
            )
            .then((response) => {
                return Promise.resolve(
                    Object.assign({}, response, {
                        results: TagList.fromResponse(response.results),
                    })
                );
            });
    }

    isTaggedWith(testName) {
        for (let tag of this._list) {
            for (let tagName of tag.names) {
                if (tagName.toLowerCase() === testName.toLowerCase()) {
                    return true;
                }
            }
        }
        return false;
    }

    addByName(tagName, addImplications) {
        if (this.isTaggedWith(tagName)) {
            return Promise.resolve();
        }

        const tag = new Tag();
        tag.names = [tagName];

        this.add(tag);

        if (addImplications !== false) {
            return Tag.get(tagName).then((actualTag) => {
                return Promise.all(
                    actualTag.implications.map((relation) =>
                        this.addByName(relation.names[0], true)
                    )
                );
            });
        }

        return Promise.resolve();
    }

    removeByName(testName) {
        for (let tag of this._list) {
            for (let tagName of tag.names) {
                if (tagName.toLowerCase() === testName.toLowerCase()) {
                    this.remove(tag);
                }
            }
        }
    }
}

TagList._itemClass = Tag;
TagList._itemName = "tag";

module.exports = TagList;
