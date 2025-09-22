<div class='content-wrapper pool-summary'>
    <section class='details'>
        <section>
            Category:
            <span class='<%= ctx.makeCssName(ctx.pool.category, 'pool') %>'><%- ctx.pool.category %></span>
        </section>

        <section>
        Aliases:<br/>
        <ul><!--
            --><% for (let name of ctx.pool.names.slice(1)) { %><!--
                --><li><%= ctx.makePoolLink(ctx.pool.id, false, false, ctx.pool, name) %></li><!--
            --><% } %><!--
        --></ul>
        </section>
    </section>

    <section class='description'>
        <hr/>
        <%= ctx.makeMarkdown(ctx.pool.description || 'This pool has no description yet.') %>
        <p>This pool has <a href='<%- ctx.formatClientLink('posts', {query: 'pool:' + ctx.pool.id + ' sort:pool'}) %>'><%- ctx.pool.postCount %> post(s)</a>.</p>
    </section>
</div>
