<div class='content-wrapper transparent' id='home'>
    <div class='messages'></div>
    <header>
        <h1><%= ctx.name %></h1>
        <aside>
            Serving <%= ctx.postCount %> posts (<%= ctx.makeFileSize(ctx.diskUsage) %>)
        </aside>
    </header>
    <% if (ctx.canListPosts) { %>
        <form class='horizontal'>
            <div class='input'>
                <%= ctx.makeButton({name: 'all-posts', value: 'Browse all posts'}) %>
                <span class='separator'>or</span>
                <%= ctx.makeTextInput({name: 'search-text', placeholder: 'enter some tags'}) %>
            </div>
            <div class='buttons'>
                <input type='submit' value='Search'/>
            </div>
        </form>
    <% } %>
    <% if (ctx.featuredPost) { %>
        <aside>
            Featured post: <%= ctx.makePostLink(ctx.featuredPost.id) %>,
            posted
            <%= ctx.makeRelativeTime(ctx.featuredPost.creationTime) %>
            by
            <%= ctx.makeUserLink(ctx.featuredPost.user) %>
        </aside>
    <% } %>
    <div class='post-container'></div>
    <footer>
        Build <a class='version' href='https://github.com/rr-/szurubooru/commits/master'><%= ctx.version %></a>
        from <%= ctx.makeRelativeTime(ctx.buildDate) %>
    </footer>
</div>
