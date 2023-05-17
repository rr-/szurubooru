<tr data-category='<%- ctx.postBan.checksum %>'><%
    <td class='name'>
        <%- ctx.postBan.checksum %>
    </td>
    <td class='time'>
        <%- ctx.makeRelativeTime(ctx.postBan.time) %>
    </td>
    <% if (ctx.canDelete) { %>
        <td class='remove'>
            <a href>Unban</a>
        </td>
    <% } %>
</tr>
