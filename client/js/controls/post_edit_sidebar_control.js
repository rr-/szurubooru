'use strict';

const views = require('../util/views.js');

class PostEditSidebarControl {
    constructor(hostNode, post, postContentControl) {
        this._hostNode = hostNode;
        this._post = post;
        this._postContentControl = postContentControl;
        this._template = views.getTemplate('post-edit-sidebar');

        this.install();
    }

    install() {
        const sourceNode = this._template({
            post: this._post,
        });
        views.replaceContent(this._hostNode, sourceNode);
    }
};

module.exports = PostEditSidebarControl;
