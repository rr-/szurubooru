<% if (ctx.post) { %>
    <a href='<%= ctx.getPostUrl(ctx.post.id, ctx.parameters) %>'>
        <div class='post-container'></div>
    </a>
<% } %>
