'use strict';

const events = require('../events.js');
const views = require('../util/views.js');

const template = views.getTemplate('post-edit-sidebar');

class PostEditSidebarControl extends events.EventTarget {
    constructor(hostNode, post, postContentControl) {
        super();
        this._hostNode = hostNode;
        this._post = post;
        this._postContentControl = postContentControl;

        views.replaceContent(this._hostNode, template({
            post: this._post,
        }));
    }
};

module.exports = PostEditSidebarControl;
