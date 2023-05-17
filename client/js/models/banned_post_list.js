const api = require("../api.js");
const uri = require("../util/uri.js");
const AbstractList = require("./abstract_list.js");
const BannedPost = require("./banned_post.js");

class BannedPostList extends AbstractList {
    constructor() {
        super();
        this._deletedBans = [];
        this.addEventListener("remove", (e) => this._evtBannedPostDeleted(e));
    }

    static get() {
        return api
            .get(uri.formatApiLink("post-ban"))
            .then((response) => {
                return Promise.resolve(
                    Object.assign({}, response, {
                        results: BannedPostList.fromResponse(
                            response.results
                        ),
                    })
                );
            });
    }

    save() {
        let promises = [];
        for (let BannedPost of this._deletedBans) {
            promises.push(BannedPost.delete());
        }

        return Promise.all(promises).then((response) => {
            this._deletedBans = [];
            return Promise.resolve();
        });
    }

    _evtBannedPostDeleted(e) {
        this._deletedBans.push(e.detail.BannedPost);
    }
}

BannedPostList._itemClass = BannedPost;
BannedPostList._itemName = "bannedPost";

module.exports = BannedPostList;
