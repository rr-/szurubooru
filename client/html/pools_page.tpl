<% if (ctx.postFlow) { %><div class='pool-list post-flow'><% } else { %><div class='pool-list'><% } %>
    <% if (ctx.response.results.length) { %>
        <ul>
          <% for (let pool of ctx.response.results) { %>
            <li data-pool-id='<%= pool.id %>'>
                <a class='thumbnail-wrapper' href='<%= ctx.canViewPools ? ctx.formatClientLink("pool", pool.id) : "" %>'>
                    <% if (ctx.canViewPosts) { %>
                        <%= ctx.makePoolThumbnails(pool.posts, ctx.postFlow) %>
                    <% } %>
                </a>
                <div class='pool-name'>
                  <%= ctx.makePoolLink(pool.id, false, false, pool, name) %>
                </div>
            </li>
          <% } %>
          <%= ctx.makeFlexboxAlign() %>
        </ul>
    <% } %>
</div>
