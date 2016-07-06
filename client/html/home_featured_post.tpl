<div class='post-container'></div>
<% if (ctx.featuredPost) { %>
    <aside>
        Featured post: <%= ctx.makePostLink(ctx.featuredPost.id) %>,
        posted
        <%= ctx.makeRelativeTime(ctx.featuredPost.creationTime) %>
        by
        <%= ctx.makeUserLink(ctx.featuredPost.user) %>
    </aside>
<% } %>
