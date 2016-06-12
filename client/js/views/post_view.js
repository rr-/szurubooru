'use strict';

const api = require('../api.js');
const router = require('../router.js');
const views = require('../util/views.js');
const keyboard = require('../util/keyboard.js');
const PostContentControl = require('../controls/post_content_control.js');
const PostNotesOverlayControl =
    require('../controls/post_notes_overlay_control.js');
const PostReadonlySidebarControl =
    require('../controls/post_readonly_sidebar_control.js');
const PostEditSidebarControl =
    require('../controls/post_edit_sidebar_control.js');
const CommentListControl = require('../controls/comment_list_control.js');
const CommentFormControl = require('../controls/comment_form_control.js');

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
            postViewNode.getBoundingClientRect().top -
            topNavNode.getBoundingClientRect().height);

        this._postContentControl = new PostContentControl(
            postContainerNode,
            ctx.post,
            () => {
                return [
                    window.innerWidth -
                        postContainerNode.getBoundingClientRect().left -
                        margin,
                    window.innerHeight -
                        topNavNode.getBoundingClientRect().height -
                        margin * 2,
                ];
            });

        new PostNotesOverlayControl(
            postContainerNode.querySelector('.post-overlay'),
            ctx.post);

        this._installSidebar(ctx);
        this._installCommentForm(ctx);
        this._installComments(ctx);

        keyboard.bind('e', () => {
            if (ctx.editMode) {
                router.show('/post/' + ctx.post.id);
            } else {
                router.show('/post/' + ctx.post.id + '/edit');
            }
        });
        keyboard.bind(['a', 'left'], () => {
            if (ctx.nextPostId) {
                router.show('/post/' + ctx.nextPostId);
            }
        });
        keyboard.bind(['d', 'right'], () => {
            if (ctx.prevPostId) {
                router.show('/post/' + ctx.prevPostId);
            }
        });
    }

    _installSidebar(ctx) {
        const sidebarContainerNode = document.querySelector(
            '#content-holder .sidebar-container');

        if (ctx.editMode) {
            new PostEditSidebarControl(
                sidebarContainerNode, ctx.post, this._postContentControl);
        } else {
            new PostReadonlySidebarControl(
                sidebarContainerNode, ctx.post, this._postContentControl);
        }
    }

    _installCommentForm(ctx) {
        const commentFormContainer = document.querySelector(
            '#content-holder .comment-form-container');
        if (!commentFormContainer) {
            return;
        }

        this._formControl = new CommentFormControl(
            commentFormContainer,
            null,
            {
                onSave: text => {
                    return api.post('/comments', {
                        postId: ctx.post.id,
                        text: text,
                    }).then(response => {
                        ctx.post.comments.push(response);
                        this._formControl.setText('');
                        this._installComments(ctx);
                    }, response => {
                        this._formControl.showError(response.description);
                    });
                },
                canCancel: false,
                minHeight: 150,
            });
        this._formControl.enterEditMode();
    }

    _installComments(ctx) {
        const commentsContainerNode = document.querySelector(
            '#content-holder .comments-container');
        if (commentsContainerNode) {
            new CommentListControl(commentsContainerNode, ctx.post.comments);
        }
    }
}

module.exports = PostView;
