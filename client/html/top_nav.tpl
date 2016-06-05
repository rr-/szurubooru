<nav id='top-nav' class='buttons'>
    <ul><!--
        --><% _.each(ctx.items, (item, key) => { %><!--
            --><% if (item.available) { %><!--
                --><li data-name='<%= key %>'><!--
                    --><a href='<%= item.url %>' accesskey='<%= item.accessKey %>'><!--
                        --><% if (item.imageUrl) { print(ctx.makeThumbnail(item.imageUrl)); } %><!--
                        --><span class='text'><%= ctx.makeAccessKey(item.name, item.accessKey) %></span><!--
                    --></a><!--
                --></li><!--
            --><% } %><!--
        --><% }) %><!--
    --></ul>
</nav>
