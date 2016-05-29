<div class='content-wrapper transparent' id='home'>
    <div class='messages'></div>
    <header>
        <h1><%= ctx.name %></h1>
    </header>
    <nav>
        <ul>
            <% if (ctx.canListPosts) { %>
                <li><a href='/posts'><%= ctx.makeAccessKey('Posts', 'P') %></a></li>
            <% } %>
            <% if (ctx.canListComments) { %>
                <li><a href='/comments'><%= ctx.makeAccessKey('Comments', 'C') %></a></li>
            <% } %>
            <% if (ctx.canListTags) { %>
                <li><a href='/tags'><%= ctx.makeAccessKey('Tags', 'T') %></a></li>
            <% } %>
            <% if (ctx.canListUsers) { %>
                <li><a href='/users'><%= ctx.makeAccessKey('Users', 'U') %></a></li>
            <% } %>
            <li><a href='/help'><%= ctx.makeAccessKey('Help', 'E') %></a></li>
        </ul>
    </nav>
    <% if (ctx.canListPosts) { %>
        <form class='horizontal'>
            <div class='input'>
                <ul>
                    <li>
                        <%= ctx.makeTextInput({id: 'search-text', name: 'search-text'}) %>
                    </li>
                </ul>
            </div>
            <div class='buttons'>
                <input type='submit' value='Search'/>
            </div>
        </form>
    <% } %>
    <div class='post-container'></div>
    <% if (ctx.featuredPost) { %>
        <aside>
            <%= ctx.makePostLink(ctx.featuredPost.id) %>
            uploaded
            <%= ctx.makeRelativeTime(ctx.featuredPost.creationTime) %>
            by
            <%= ctx.makeUserLink(ctx.featuredPost.user) %>
        </aside>
    <% } %>
    <footer>
        Serving <%= ctx.postCount %> posts (<%= ctx.makeFileSize(ctx.diskUsage) %>)
        &bull;
        Running <a class='version' href='https://github.com/rr-/szurubooru/commits/master'><%= ctx.version %></a>
        &bull;
        Built <%= ctx.makeRelativeTime(ctx.buildDate) %>
    </footer>
</div>
