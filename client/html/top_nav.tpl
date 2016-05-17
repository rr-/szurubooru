<nav id='top-nav' class='text-nav'>
    <ul><!--
        --><% _.each(items, (item, key) => { %><!--
            --><% if (item.available) { %><!--
                --><li data-name='<%= key %>'><!--
                    --><a href='<%= item.url %>' accesskey='<%= item.accessKey %>'><!--
                        --><% if (item.imageUrl) { print(makeThumbnail(item.imageUrl)); } %><!--
                        --><span class='text'><%- item.name %></span><!--
                    --></a><!--
                --></li><!--
            --><% } %><!--
        --><% }) %><!--
    --></ul>
</nav>
