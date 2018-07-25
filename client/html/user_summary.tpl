<div id='user-summary'>
    <%= ctx.makeThumbnail(ctx.user.avatarUrl) %>
    <ul class='basic-info'>
        <li>Registered: <%= ctx.makeRelativeTime(ctx.user.creationTime) %></li>
        <li>Last seen: <%= ctx.makeRelativeTime(ctx.user.lastLoginTime) %></li>
        <li>Rank: <%- ctx.user.rankName.toLowerCase() %></li>
    </ul>

    <div>
        <nav>
            <p><strong>Quick links</strong></p>
            <ul>
                <li><a href='<%- ctx.formatClientLink('posts', {query: 'submit:' + ctx.user.name}) %>'><%- ctx.user.uploadedPostCount %> uploads</a></li>
                <li><a href='<%- ctx.formatClientLink('posts', {query: 'fav:' + ctx.user.name}) %>'><%- ctx.user.favoritePostCount %> favorites</a></li>
                <li><a href='<%- ctx.formatClientLink('posts', {query: 'comment:' + ctx.user.name}) %>'><%- ctx.user.commentCount %> comments</a></li>
            </ul>
        </nav>

        <% if (ctx.isLoggedIn) { %>
            <nav>
                <p><strong>Only visible to you</strong></p>
                <ul>
                    <li><a href='<%- ctx.formatClientLink('posts', {query: 'special:liked'}) %>'><%- ctx.user.likedPostCount %> liked posts</a></li>
                    <li><a href='<%- ctx.formatClientLink('posts', {query: 'special:disliked'}) %>'><%- ctx.user.dislikedPostCount %> disliked posts</a></li>
                </ul>
            </nav>
        <% } %>
    </div>
</div>
