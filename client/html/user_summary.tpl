<div id='user-summary'>
    <%= makeThumbnail(user.avatarUrl) %>
    <ul class='basic-info'>
        <li>Registered: <%= makeRelativeTime(user.creationTime) %></li>
        <li>Last seen: <%= makeRelativeTime(user.lastLoginTime) %></li>
        <li>Rank: <%= user.rankName.toLowerCase() %></li>
    </ul>

    <div>
        <nav class='plain-nav'>
            <p><strong>Quick links</strong></p>
            <ul>
                <li><a href='/posts/text=submit:<%= user.name %>'>Uploads</a></li>
                <li><a href='/posts/text=fav:<%= user.name %>'>Favorites</a></li>
                <li><a href='/posts/text=comment:<%= user.name %>'>Posts commented on</a></li>
            </ul>
        </nav>

        <% if (isLoggedIn) { %>
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
