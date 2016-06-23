<div class='content-wrapper tag-summary'>
    <section class='details'>
        <section>
            Category:
            <span class='<%= ctx.makeCssName(ctx.tag.category, 'tag') %>'><%- ctx.tag.category %></span>
        </section>

        <section>
        Aliases:<br/>
        <ul><!--
            --><% for (let name of ctx.tag.names.slice(1)) { %><!--
                --><li><%= ctx.makeTagLink(name) %></li><!--
            --><% } %><!--
        --></ul>
        </section>

        <section>
        Implications:<br/>
        <ul><!--
            --><% for (let tag of ctx.tag.implications) { %><!--
                --><li><%= ctx.makeTagLink(tag) %></li><!--
            --><% } %><!--
        --></ul>
        </section>

        <section>
        Suggestions:<br/>
        <ul><!--
            --><% for (let tag of ctx.tag.suggestions) { %><!--
                --><li><%= ctx.makeTagLink(tag) %></li><!--
            --><% } %><!--
        --></ul>
        </section>
    </section>

    <section class='description'>
        <hr/>
        <%= ctx.makeMarkdown(ctx.tag.description || 'This tag has no description yet.') %>
    </section>
</div>
