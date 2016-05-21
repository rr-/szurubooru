<div id='user-summary'>
    <%= ctx.makeThumbnail(ctx.user.avatarUrl) %>
    <ul class='basic-info'>
        <li>Registered: <%= ctx.makeRelativeTime(ctx.user.creationTime) %></li>
        <li>Last seen: <%= ctx.makeRelativeTime(ctx.user.lastLoginTime) %></li>
        <li>Rank: <%= ctx.user.rankName.toLowerCase() %></li>
    </ul>

    <div>
        <nav class='plain-nav'>
            <p><strong>Quick links</strong></p>
            <ul>
                <li><a href='/posts/text=submit:<%= ctx.user.name %>'>Uploads</a></li>
                <li><a href='/posts/text=fav:<%= ctx.user.name %>'>Favorites</a></li>
                <li><a href='/posts/text=comment:<%= ctx.user.name %>'>Posts commented on</a></li>
            </ul>
        </nav>

        <% if (ctx.isLoggedIn) { %>
            <nav class='plain-nav'>
                <p><strong>Only visible to you</strong></p>
                <ul>
                    <li><a href='/posts/text=special:liked'>Liked posts</a></li>
                    <li><a href='/posts/text=special:disliked'>Disliked posts</a></li>
                </ul>
            </nav>
        <% } %>
    </div>
</div>
