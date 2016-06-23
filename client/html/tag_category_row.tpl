<tr data-category='<%- ctx.tagCategory.name %>'
    <% if (ctx.tagCategory.isDefault) { %> class='default' <% } %>
>
    <td class='name'>
        <% if (ctx.canEditName) { %>
            <%= ctx.makeTextInput({value: ctx.tagCategory.name, required: true}) %>
        <% } else { %>
            <%- ctx.tagCategory.name %>
        <% } %>
    </td>
    <td class='color'>
        <% if (ctx.canEditColor) { %>
            <%= ctx.makeColorInput({value: ctx.tagCategory.color}) %>
        <% } else { %>
            <%- ctx.tagCategory.color %>
        <% } %>
    </td>
    <td class='usages'>
        <% if (ctx.tagCategory.name) { %>
            <a href='/tags/text=category:<%- encodeURIComponent(ctx.tagCategory.name) %>'>
                <%- ctx.tagCategory.tagCount %>
            </a>
        <% } else { %>
            <%- ctx.tagCategory.tagCount %>
        <% } %>
    </td>
    <% if (ctx.canDelete) { %>
        <td class='remove'>
            <% if (ctx.tagCategory.tagCount) { %>
                <a class='inactive' title="Can't delete category in use">Remove</a>
            <% } else { %>
                <a href='#'>Remove</a>
            <% } %>
        </td>
    <% } %>
    <% if (ctx.canSetDefault) { %>
        <td class='set-default'>
            <a href='#'>Make default</a>
        </td>
    <% } %>
</tr>
