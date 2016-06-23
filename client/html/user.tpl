<div class='content-wrapper' id='user'>
    <h1><%- ctx.user.name %></h1>
    <nav class='buttons'><!--
        --><ul><!--
            --><li data-name='summary'><a href='/user/<%- ctx.user.name %>'>Summary</a></li><!--
            --><% if (ctx.canEditAnything) { %><!--
                --><li data-name='edit'><a href='/user/<%- ctx.user.name %>/edit'>Account settings</a></li><!--
            --><% } %><!--
            --><% if (ctx.canDelete) { %><!--
                --><li data-name='delete'><a href='/user/<%- ctx.user.name %>/delete'>Account deletion</a></li><!--
            --><% } %><!--
        --></ul><!--
    --></nav>
    <div id='user-content-holder'></div>
</div>
