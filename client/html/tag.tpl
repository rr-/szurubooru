<div class='content-wrapper' id='tag'>
    <h1><%- ctx.getPrettyName(ctx.tag.names[0]) %></h1>
    <nav class='buttons'><!--
        --><ul><!--
            --><li data-name='view'><a href='<%- ctx.formatClientLink('posts', {query: ctx.escapeTagName(ctx.tag.names[0])}) %>'>View</a></li><!--
            --><li data-name='summary'><a href='<%- ctx.formatClientLink('tag', ctx.tag.names[0]) %>'>Summary</a></li><!--
            --><% if (ctx.canEditAnything) { %><!--
                --><li data-name='edit'><a href='<%- ctx.formatClientLink('tag', ctx.tag.names[0], 'edit') %>'>Edit</a></li><!--
            --><% } %><!--
            --><% if (ctx.canMerge) { %><!--
                --><li data-name='merge'><a href='<%- ctx.formatClientLink('tag', ctx.tag.names[0], 'merge') %>'>Merge with&hellip;</a></li><!--
            --><% } %><!--
            --><% if (ctx.canDelete) { %><!--
                --><li data-name='delete'><a href='<%- ctx.formatClientLink('tag', ctx.tag.names[0], 'delete') %>'>Delete</a></li><!--
            --><% } %><!--
        --></ul><!--
    --></nav>
    <div class='tag-content-holder'></div>
</div>
