<article class='details'>
    <section class='download'>
        <a rel='external' href='<%= ctx.post.contentUrl %>'>
            <i class='fa fa-download'></i><!--
        --><%= ctx.makeFileSize(ctx.post.fileSize) %> <!--
            --><%= {
                'image/gif': 'GIF',
                'image/jpeg': 'JPEG',
                'image/png': 'PNG',
                'video/webm': 'WEBM',
                'application/x-shockwave-flash': 'SWF',
            }[ctx.post.mimeType] %>
        </a>
        (<%= ctx.post.canvasWidth %>x<%= ctx.post.canvasHeight %>)
    </section>

    <section class='upload-info'>
        <%= ctx.makeUserLink(ctx.post.user) %>,
        <%= ctx.makeRelativeTime(ctx.post.creationTime) %>
    </section>

    <section class='safety'>
        <i class='fa fa-circle safety-<%= ctx.post.safety %>'></i><!--
        --><%= ctx.post.safety[0].toUpperCase() + ctx.post.safety.slice(1) %>
    </section>

    <section class='zoom'>
        <a class='fit-original' href='#'>Original zoom</a> &middot;
        <a class='fit-width' href='#'>fit width</a> &middot;
        <a class='fit-height' href='#'>height</a> &middot;
        <a class='fit-both' href='#'>both</a>
    </section>

    <section class='search'>
        Search on
        <a href='http://iqdb.org/?url=<%= ctx.post.contentUrl %>'>IQDB</a> &middot;
        <a href='https://www.google.com/searchbyimage?&image_url=<%= ctx.post.contentUrl %>'>Google Images</a>
    </section>

    <section class='social'>
        <div class='score'>
            <% if (ctx.canScorePosts) { %>
                <a class='upvote' href='#'>
                    <% if (ctx.post.ownScore == 1) { %>
                        <i class='fa fa-thumbs-up'></i>
                    <% } else { %>
                        <i class='fa fa-thumbs-o-up'></i>
                    <% } %>
                    <span class='vim-nav-hint'>upvote</span>
                    <span class='vim-nav-hint'>like</span>
                </a>
            <% } else { %>
                <a class='upvote inactive'>
                    <i class='fa fa-thumbs-o-up'></i>
                </a>
            <% } %>
            <span class='value'><%= ctx.post.score %></span>
            <% if (ctx.canScorePosts) { %>
                <a class='downvote' href='#'>
                    <% if (ctx.post.ownScore == -1) { %>
                        <i class='fa fa-thumbs-down'></i>
                    <% } else { %>
                        <i class='fa fa-thumbs-o-down'></i>
                    <% } %>
                    <span class='vim-nav-hint'>downvote</span>
                    <span class='vim-nav-hint'>dislike</span>
                </a>
            <% } %>
        </div>

        <div class='fav'>
            <% if (ctx.canFavoritePosts) { %>
                <% if (ctx.post.ownFavorite) { %>
                    <a class='remove-favorite' href='#'>
                        <i class='fa fa-heart'></i>
                <% } else { %>
                    <a class='add-favorite' href='#'>
                        <i class='fa fa-heart-o'></i>
                <% } %>
            <% } else { %>
                <a class='add-favorite inactive'>
                    <i class='fa fa-heart-o'></i>
            <% } %>
                <span class='vim-nav-hint'>add to favorites</span>
            </a>
            <span class='value'><%= ctx.post.favoriteCount %></span>
        </div>
    </section>
</article>

<nav class='tags'>
    <h1>Tags (<%= ctx.post.tags.length %>)</h1>
    <ul><!--
        --><% for (let tag of ctx.post.tags) { %><!--
            --><li><!--
                --><% if (ctx.canViewTags) { %><!--
                --><a href='/tag/<%= tag %>' class='tag-<%= ctx.getTagCategory(tag) %>'><!--
                    --><i class='fa fa-tag'></i><!--
                --><% } %><!--
                --><% if (ctx.canListPosts) { %><!--
                    --></a><!--
                --><% } %><!--
                --><% if (ctx.canListPosts) { %><!--
                    --><a href='/posts/text=<%= tag %>' class='tag-<%= ctx.getTagCategory(tag) %>'><!--
                --><% } %><!--
                    --><%= tag %><!--
                --><% if (ctx.canListPosts) { %><!--
                    --></a><!--
                --><% } %><!--
                --><span class='count'><%= ctx.getTagUsages(tag) %></span><!--
            --></li><!--
        --><% } %><!--
    --></ul>
</nav>
