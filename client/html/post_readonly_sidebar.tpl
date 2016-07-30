<div class='readonly-sidebar'>
    <article class='details'>
        <section class='download'>
            <a rel='external' href='<%- ctx.post.contentUrl %>'>
                <i class='fa fa-download'></i><!--
            --><%= ctx.makeFileSize(ctx.post.fileSize) %> <!--
                --><%- {
                    'image/gif': 'GIF',
                    'image/jpeg': 'JPEG',
                    'image/png': 'PNG',
                    'video/webm': 'WEBM',
                    'application/x-shockwave-flash': 'SWF',
                }[ctx.post.mimeType] %>
            </a>
            (<%- ctx.post.canvasWidth %>x<%- ctx.post.canvasHeight %>)
        </section>

        <section class='upload-info'>
            <%= ctx.makeUserLink(ctx.post.user) %>,
            <%= ctx.makeRelativeTime(ctx.post.creationTime) %>
        </section>

        <section class='safety'>
            <i class='fa fa-circle safety-<%- ctx.post.safety %>'></i><!--
            --><%- ctx.post.safety[0].toUpperCase() + ctx.post.safety.slice(1) %>
        </section>

        <section class='zoom'>
            <a class='fit-original' href='#'>Original zoom</a> &middot;
            <a class='fit-width' href='#'>fit width</a> &middot;
            <a class='fit-height' href='#'>height</a> &middot;
            <a class='fit-both' href='#'>both</a>
        </section>

        <section class='search'>
            Search on
            <a href='http://iqdb.org/?url=<%- encodeURIComponent(ctx.post.contentUrl) %>'>IQDB</a> &middot;
            <a href='https://www.google.com/searchbyimage?&image_url=<%- encodeURIComponent(ctx.post.contentUrl) %>'>Google Images</a>
        </section>

        <section class='social'>
            <div class='score-container'></div>

            <div class='fav-container'></div>
        </section>
    </article>

    <% if (ctx.post.relations.length) { %>
        <nav class='relations'>
            <h1>Relations (<%- ctx.post.relations.length %>)</h1>
            <ul><!--
                --><% for (let post of ctx.post.relations) { %><!--
                    --><li><!--
                        --><a href='<%= ctx.getPostUrl(post.id, ctx.parameters) %>'><!--
                            --><%= ctx.makeThumbnail(post.thumbnailUrl) %><!--
                        --></a><!--
                    --></li><!--
                --><% } %><!--
            --></ul>
        </nav>
    <% } %>

    <nav class='tags'>
        <h1>Tags (<%- ctx.post.tags.length %>)</h1>
        <% if (ctx.post.tags.length) { %>
            <ul class="compact-tags"><!--
                --><% for (let tag of ctx.post.tags) { %><!--
                    --><li><!--
                        --><% if (ctx.canViewTags) { %><!--
                        --><a href='/tag/<%- encodeURIComponent(tag) %>' class='<%= ctx.makeCssName(ctx.getTagCategory(tag), 'tag') %>'><!--
                            --><i class='fa fa-tag'></i><!--
                        --><% } %><!--
                        --><% if (ctx.canViewTags) { %><!--
                            --></a><!--
                        --><% } %><!--
                        --><% if (ctx.canListPosts) { %><!--
                            --><a href='/posts/query=<%- encodeURIComponent(tag) %>' class='<%= ctx.makeCssName(ctx.getTagCategory(tag), 'tag') %>'><!--
                        --><% } %><!--
                            --><%- tag %><!--
                        --><% if (ctx.canListPosts) { %><!--
                            --></a><!--
                        --><% } %><!--
                        --><span class='append'><%- ctx.getTagUsages(tag) %></span><!--
                    --></li><!--
                --><% } %><!--
            --></ul>
        <% } else { %>
            <p>
                No tags yet!
                <% if (ctx.canEditPosts) { %>
                    <a href='<%= ctx.getPostEditUrl(ctx.post.id, ctx.parameters) %>'>Add some.</a>
                <% } %>
            </p>
        <% } %>
    </nav>
</div>
