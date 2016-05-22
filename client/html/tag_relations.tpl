<div class='tag-relations'>
    <% if (ctx.suggestions.length) { %>
        <ul class='tag-suggestions'>
            <% _.each(ctx.suggestions.slice(0, 20), tagName => { %>
                <li><%= ctx.makeTagLink(tagName) %></li>
            <% }) %>
        </ul>
    <% } %>
    <% if (ctx.siblings.length) { %>
        <ul class='tag-siblings'>
            <% _.each(ctx.siblings.slice(0, 20), tagName => { %>
                <li><%= ctx.makeTagLink(tagName) %></li>
            <% }) %>
        </ul>
    <% } %>
</div>
