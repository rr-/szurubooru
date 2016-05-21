<div class='user-list'>
    <ul><!--
        --><% _.each(ctx.results, user => { %><!--
            --><li>
                <div class='wrapper'>
                    <a class='image' href='/user/<%= user.name %>'><%= ctx.makeThumbnail(user.avatarUrl) %></a>
                    <div class='details'>
                        <a href='/user/<%= user.name %>'><%= user.name %></a><br/>
                        Registered: <%= ctx.makeRelativeTime(user.creationTime) %><br/>
                        Last seen: <%= ctx.makeRelativeTime(user.lastLoginTime) %>
                    </div>
                </div>
            </li><!--
        --><% }) %><!--
        --><%= ctx.makeFlexboxAlign() %><!--
    --></ul>
</div>
