<div class='pool-navigator-container'>
  <div class='pool-info-wrapper <%= ctx.isActivePool ? "active" : "" %>'>
    <span class='prev'>
      <% if (ctx.canViewPosts && ctx.prevPost) { %>
        <a class='<%- ctx.linkClass %>' href='<%= ctx.getPostUrl(ctx.prevPost.id, ctx.parameters) %>'>
      <% } %>
        ‹ prev
      <% if (ctx.canViewPosts && ctx.prevPost) { %>
        </a>
      <% } %>
    </span>
    <span class='pool-name'>
      <% if (ctx.canViewPools) { %>
        <a class='<%- ctx.linkClass %>' href='<%= ctx.formatClientLink("pool", ctx.pool.id) %>'>
      <% } %>
        Pool: <%- ctx.pool.names[0] %>
      <% if (ctx.canViewPools) { %>
        </a>
      <% } %>
    </span>
    <span class='next'>
      <% if (ctx.canViewPosts && ctx.nextPost) { %>
        <a class='<%- ctx.linkClass %>' href='<%= ctx.getPostUrl(ctx.nextPost.id, ctx.parameters) %>'>
      <% } %>
        next ›
      <% if (ctx.canViewPosts && ctx.nextPost) { %>
        </a>
      <% } %>
    </span>
  </div>
</div>
