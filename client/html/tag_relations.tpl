<div class='tag-relations'>
    <% if (ctx.suggestions.length) { %>
        <ul class='tag-suggestions'>
            <% for (let tagName of ctx.suggestions.slice(0, 20)) { %>
                <li><%= ctx.makeTagLink(tagName) %></li>
            <% } %>
        </ul>
    <% } %>
    <% if (ctx.siblings.length) { %>
        <ul class='tag-siblings'>
            <% for (let tagName of ctx.siblings.slice(0, 20)) { %>
                <li><%= ctx.makeTagLink(tagName) %></li>
            <% } %>
        </ul>
    <% } %>
</div>
