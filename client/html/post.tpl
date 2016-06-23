<div class='content-wrapper transparent post-view'>
    <aside class='sidebar'>
        <nav class='buttons'>
            <article class='next-post'>
                <% if (ctx.nextPostId) { %>
                    <% if (ctx.searchQuery && ctx.searchQuery.text) { %>
                        <a href='/post/<%- encodeURIComponent(ctx.nextPostId) %>/text=<%- encodeURIComponent(ctx.searchQuery.text) %>'>
                    <% } else { %>
                        <a href='/post/<%- encodeURIComponent(ctx.nextPostId) %>'>
                    <% } %>
                <% } else { %>
                    <a class='inactive'>
                <% } %>
                    <i class='fa fa-chevron-left'></i>
                    <span class='vim-nav-hint'>Next post</span>
                </a>
            </article>
            <article class='previous-post'>
                <% if (ctx.prevPostId) { %>
                    <% if (ctx.searchQuery && ctx.searchQuery.text) { %>
                        <a href='/post/<%- encodeURIComponent(ctx.prevPostId) %>/text=<%- encodeURIComponent(ctx.searchQuery.text) %>'>
                    <% } else { %>
                        <a href='/post/<%- encodeURIComponent(ctx.prevPostId) %>'>
                    <% } %>
                <% } else { %>
                    <a class='inactive'>
                <% } %>
                    <i class='fa fa-chevron-right'></i>
                    <span class='vim-nav-hint'>Previous post</span>
                </a>
            </article>
            <article class='edit-post'>
                <% if (ctx.editMode) { %>
                    <a href='/post/<%- encodeURIComponent(ctx.post.id) %>'>
                        <i class='fa fa-eye'></i>
                        <span class='vim-nav-hint'>Back to view mode</span>
                    </a>
                <% } else { %>
                    <% if (ctx.canEditPosts) { %>
                        <% if (ctx.searchQuery && ctx.searchQuery.text) { %>
                            <a href='/post/<%- encodeURIComponent(ctx.post.id) %>/edit/text=<%- encodeURIComponent(ctx.searchQuery.text) %>'>
                        <% } else { %>
                            <a href='/post/<%- encodeURIComponent(ctx.post.id) %>/edit'>
                        <% } %>
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
