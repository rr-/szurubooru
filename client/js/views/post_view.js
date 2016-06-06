'use strict';

const views = require('../util/views.js');
const PostContentControl = require('../controls/post_content_control.js');
const PostNotesOverlayControl
    = require('../controls/post_notes_overlay_control.js');
const PostReadonlySidebarControl
    = require('../controls/post_readonly_sidebar_control.js');
const PostEditSidebarControl
    = require('../controls/post_edit_sidebar_control.js');

class PostView {
    constructor() {
        this._template = views.getTemplate('post');
    }

    render(ctx) {
        const target = document.getElementById('content-holder');
        const source = this._template(ctx);

        const postContainerNode = source.querySelector('.post-container');
        const sidebarNode = source.querySelector('.sidebar');

        views.listenToMessages(source);
        views.showView(target, source);

        const topNavNode = document.body.querySelector('#top-nav');
        const postViewNode = document.body.querySelector('.content-wrapper');

        const margin = (
            postViewNode.getBoundingClientRect().top
            - topNavNode.getBoundingClientRect().height);

        this._postContentControl = new PostContentControl(
            postContainerNode,
            ctx.post,
            () => {
                return [
                    window.innerWidth
                        - postContainerNode.getBoundingClientRect().left
                        - margin,
                    window.innerHeight
                        - topNavNode.getBoundingClientRect().height
                        - margin * 2,
                ];
            });

        new PostNotesOverlayControl(
            postContainerNode.querySelector('.post-overlay'),
            ctx.post);

        if (ctx.editMode) {
            new PostEditSidebarControl(
                postViewNode.querySelector('.sidebar-container'),
                ctx.post,
                this._postContentControl);
        } else {
            new PostReadonlySidebarControl(
                postViewNode.querySelector('.sidebar-container'),
                ctx.post,
                this._postContentControl);
        }
    }

}

module.exports = PostView;
