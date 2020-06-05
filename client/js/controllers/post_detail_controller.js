"use strict";

const router = require("../router.js");
const api = require("../api.js");
const misc = require("../util/misc.js");
const uri = require("../util/uri.js");
const settings = require("../models/settings.js");
const Post = require("../models/post.js");
const PostList = require("../models/post_list.js");
const PostDetailView = require("../views/post_detail_view.js");
const BasePostController = require("./base_post_controller.js");
const EmptyView = require("../views/empty_view.js");

class PostDetailController extends BasePostController {
    constructor(ctx, section) {
        super(ctx);

        Post.get(ctx.parameters.id).then(
            (post) => {
                this._id = ctx.parameters.id;
                post.addEventListener("change", (e) =>
                    this._evtSaved(e, section)
                );
                this._installView(post, section);
            },
            (error) => {
                this._view = new EmptyView();
                this._view.showError(error.message);
            }
        );
    }

    showSuccess(message) {
        this._view.showSuccess(message);
    }

    _installView(post, section) {
        this._view = new PostDetailView({
            post: post,
            section: section,
            canMerge: api.hasPrivilege("posts:merge"),
        });

        this._view.addEventListener("select", (e) => this._evtSelect(e));
        this._view.addEventListener("merge", (e) => this._evtMerge(e));
    }

    _evtSelect(e) {
        this._view.clearMessages();
        this._view.disableForm();
        Post.get(e.detail.postId).then(
            (post) => {
                this._view.selectPost(post);
                this._view.enableForm();
            },
            (error) => {
                this._view.showError(error.message);
                this._view.enableForm();
            }
        );
    }

    _evtSaved(e, section) {
        misc.disableExitConfirmation();
        if (this._id !== e.detail.post.id) {
            router.replace(
                uri.formatClientLink("post", e.detail.post.id, section),
                null,
                false
            );
        }
    }

    _evtMerge(e) {
        this._view.clearMessages();
        this._view.disableForm();
        e.detail.post
            .merge(e.detail.targetPost.id, e.detail.useOldContent)
            .then(
                () => {
                    this._installView(e.detail.post, "merge");
                    this._view.showSuccess("Post merged.");
                    router.replace(
                        uri.formatClientLink(
                            "post",
                            e.detail.targetPost.id,
                            "merge"
                        ),
                        null,
                        false
                    );
                },
                (error) => {
                    this._view.showError(error.message);
                    this._view.enableForm();
                }
            );
    }
}

module.exports = (router) => {
    router.enter(["post", ":id", "merge"], (ctx, next) => {
        ctx.controller = new PostDetailController(ctx, "merge");
    });
};
