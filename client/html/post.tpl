<div class='content-wrapper transparent post-view'>
    <aside class='sidebar'>
        <nav class='buttons'>
            <article class='next-post'>
                <% if (ctx.nextPostId) { %>
                    <a href='<%= ctx.getPostUrl(ctx.nextPostId, ctx.parameters) %>'>
                <% } else { %>
                    <a class='inactive'>
                <% } %>
                    <i class='fa fa-chevron-left'></i>
                    <span class='vim-nav-hint'>Next post</span>
                </a>
            </article>
            <article class='previous-post'>
                <% if (ctx.prevPostId) { %>
                    <a href='<%= ctx.getPostUrl(ctx.prevPostId, ctx.parameters) %>'>
                <% } else { %>
                    <a class='inactive'>
                <% } %>
                    <i class='fa fa-chevron-right'></i>
                    <span class='vim-nav-hint'>Previous post</span>
                </a>
            </article>
            <article class='edit-post'>
                <% if (ctx.editMode) { %>
                    <a href='<%= ctx.getPostUrl(ctx.post.id, ctx.parameters) %>'>
                        <i class='fa fa-reply'></i>
                        <span class='vim-nav-hint'>Back to view mode</span>
                    </a>
                <% } else { %>
                    <% if (ctx.canEditPosts) { %>
                        <a href='<%= ctx.getPostEditUrl(ctx.post.id, ctx.parameters) %>'>
                    <% } else { %>
                        <a class='inactive'>
                    <% } %>
                        <i class='fa fa-pencil'></i>
                        <span class='vim-nav-hint'>Edit post</span>
                    </a>
                <% } %>
            </article>
        </nav>

        <div class='sidebar-container'></div>
    </aside>

    <div class='content'>
        <div class='post-container'></div>

        <% if (ctx.canListComments) { %>
            <div class='comments-container'></div>
        <% } %>

        <% if (ctx.canCreateComments) { %>
            <h2>Add comment</h2>
            <div class='comment-form-container'></div>
        <% } %>
    </div>
</div>
