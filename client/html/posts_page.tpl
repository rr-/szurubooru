<div class='post-list'>
    <% if (ctx.results.length) { %>
        <ul>
            <% for (let post of ctx.results) { %>
                <li>
                    <% if (ctx.canViewPosts) { %>
                        <% if (ctx.searchQuery && ctx.searchQuery.text) { %>
                            <a href='/post/<%- encodeURIComponent(post.id) %>/text=<%- encodeURIComponent(ctx.searchQuery.text) %>' title='@<%- post.id %> (<%- post.type %>)&#10;&#10;Tags: <%- post.tags.map(tag => '#' + tag).join(' ') %>'>
                        <% } else { %>
                            <a href='/post/<%- encodeURIComponent(post.id) %>' title='@<%- post.id %> (<%- post.type %>)&#10;&#10;Tags: <%- post.tags.map(tag => '#' + tag).join(' ') %>'>
                        <% } %>
                    <% } else { %>
                        <a>
                    <% } %>
                        <%= ctx.makeThumbnail(post.thumbnailUrl) %>
                        <span class='type' data-type='<%- post.type %>'>
                            <%- post.type %>
                        </span>
                        <% if (post.score || post.favoriteCount || post.commentCount) { %>
                            <span class='stats'>
                                <% if (post.score) { %>
                                    <span class='icon'>
                                        <i class='fa fa-star'></i>
                                        <%- post.score %>
                                    </span>
                                <% } %>
                                <% if (post.favoriteCount) { %>
                                    <span class='icon'>
                                        <i class='fa fa-heart'></i>
                                        <%- post.favoriteCount %>
                                    </span>
                                <% } %>
                                <% if (post.commentCount) { %>
                                    <span class='icon'>
                                        <i class='fa fa-commenting'></i>
                                        <%- post.commentCount %>
                                    </span>
                                <% } %>
                            </span>
                        <% } %>
                    </a>
                </li>
            <% } %>
            <%= ctx.makeFlexboxAlign() %>
        </ul>
    <% } %>
</div>
