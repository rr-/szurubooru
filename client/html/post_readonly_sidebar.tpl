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
                    'image/webp': 'WEBP',
                    'image/bmp': 'BMP',
                    'image/avif': 'AVIF',
                    'image/heif': 'HEIF',
                    'image/heic': 'HEIC',
                    'video/webm': 'WEBM',
                    'video/mp4': 'MPEG-4',
                    'video/quicktime': 'MOV',
                    'application/x-shockwave-flash': 'SWF',
                }[ctx.post.mimeType] %>
            </a>
            (<%- ctx.post.canvasWidth %>x<%- ctx.post.canvasHeight %>)
            <% if (ctx.post.flags.length) { %><!--
                --><% if (ctx.post.flags.includes('loop')) { %><i class='fa fa-repeat'></i><% } %><!--
                --><% if (ctx.post.flags.includes('sound')) { %><i class='fa fa-volume-up'></i><% } %>
            <% } %>
        </section>

        <section class='upload-info'>
            <%= ctx.makeUserLink(ctx.post.user) %>,
            <%= ctx.makeRelativeTime(ctx.post.creationTime) %>
        </section>

        <% if (ctx.enableSafety) { %>
            <section class='safety'>
                <i class='fa fa-circle safety-<%- ctx.post.safety %>'></i><!--
                --><%- ctx.post.safety[0].toUpperCase() + ctx.post.safety.slice(1) %>
            </section>
        <% } %>

        <section class='zoom'>
            <a href class='fit-original'>Original zoom</a> &middot;
            <a href class='fit-width'>fit width</a> &middot;
            <a href class='fit-height'>height</a> &middot;
            <a href class='fit-both'>both</a>
        </section>

        <% if (ctx.post.source) { %>
            <section class='source'>
                Source: <% for (let i = 0; i < ctx.post.sourceSplit.length; i++) { %>
                    <% if (i != 0) { %>&middot;<% } %>
                    <a href='<%- ctx.post.sourceSplit[i] %>' title='<%- ctx.post.sourceSplit[i] %>'><%- ctx.extractRootDomain(ctx.post.sourceSplit[i]) %></a>
                <% } %>
            </section>
        <% } %>

        <section class='search'>
            Search on
            <a href='http://iqdb.org/?url=<%- encodeURIComponent(ctx.post.fullContentUrl) %>'>IQDB</a> &middot;
            <a href='https://danbooru.donmai.us/posts?tags=md5:<%- ctx.post.checksumMD5 %>'>Danbooru</a> &middot;
            <a href='https://www.google.com/searchbyimage?&image_url=<%- encodeURIComponent(ctx.post.fullContentUrl) %>'>Google Images</a>
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
            <ul class='compact-tags'><!--
                --><% for (let tag of ctx.post.tags) { %><!--
                    --><li><!--
                        --><% if (ctx.canViewTags) { %><!--
                        --><a href='<%- ctx.formatClientLink('tag', tag.names[0]) %>' class='<%= ctx.makeCssName(tag.category, 'tag') %>'><!--
                            --><i class='fa fa-tag'></i><!--
                        --><% } %><!--
                        --><% if (ctx.canViewTags) { %><!--
                            --></a><!--
                        --><% } %><!--
                        --><% if (ctx.canListPosts) { %><!--
                            --><a href='<%- ctx.formatClientLink('posts', {query: ctx.escapeTagName(tag.names[0])}) %>' class='<%= ctx.makeCssName(tag.category, 'tag') %>'><!--
                        --><% } %><!--
                            --><%- ctx.getPrettyName(tag.names[0]) %>&#32;<!--
                        --><% if (ctx.canListPosts) { %><!--
                            --></a><!--
                        --><% } %><!--
                        --><span class='tag-usages' data-pseudo-content='<%- tag.postCount %>'></span><!--
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
