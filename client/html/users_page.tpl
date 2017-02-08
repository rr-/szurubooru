<div class='user-list'>
    <ul><!--
        --><% for (let user of ctx.response.results) { %><!--
            --><li>
                <div class='wrapper'>
                    <% if (ctx.canViewUsers) { %>
                        <a class='image' href='<%- ctx.formatClientLink('user', user.name) %>'>
                    <% } %>
                        <%= ctx.makeThumbnail(user.avatarUrl) %>
                    <% if (ctx.canViewUsers) { %>
                        </a>
                    <% } %>
                    <div class='details'>
                        <% if (ctx.canViewUsers) { %>
                            <a href='<%- ctx.formatClientLink('user', user.name) %>'>
                        <% } %>
                            <%- user.name %>
                        <% if (ctx.canViewUsers) { %>
                            </a>
                        <% } %>
                        <br/>
                        Registered: <%= ctx.makeRelativeTime(user.creationTime) %><br/>
                        Last seen: <%= ctx.makeRelativeTime(user.lastLoginTime) %>
                    </div>
                </div>
            </li><!--
        --><% } %><!--
        --><%= ctx.makeFlexboxAlign() %><!--
    --></ul>
</div>
