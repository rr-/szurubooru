<li class="<%= ctx.makeCssName(ctx.metric.tag.category, 'tag') %><%
            if (ctx.selected) { %> selected<% } %>">
    <a href class="<%= ctx.makeCssName(ctx.metric.tag.category, 'tag') %><%
            if (ctx.selected) { %> selected<% } %>"><%
        %><%- ctx.metric.tag.names[0] %></a>
</li>