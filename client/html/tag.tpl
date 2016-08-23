<div class='content-wrapper tag'>
    <h1><%- ctx.tag.names[0] %></h1>
    <nav class='buttons'><!--
        --><ul><!--
            --><li data-name='summary'><a href='/tag/<%- encodeURIComponent(ctx.tag.names[0]) %>'>Summary</a></li><!--
            --><% if (ctx.canEditAnything) { %><!--
                --><li data-name='edit'><a href='/tag/<%- encodeURIComponent(ctx.tag.names[0]) %>/edit'>Edit</a></li><!--
            --><% } %><!--
            --><% if (ctx.canMerge) { %><!--
                --><li data-name='merge'><a href='/tag/<%- encodeURIComponent(ctx.tag.names[0]) %>/merge'>Merge with&hellip;</a></li><!--
            --><% } %><!--
            --><% if (ctx.canDelete) { %><!--
                --><li data-name='delete'><a href='/tag/<%- encodeURIComponent(ctx.tag.names[0]) %>/delete'>Delete</a></li><!--
            --><% } %><!--
        --></ul><!--
    --></nav>
    <div class='tag-content-holder'></div>
</div>
