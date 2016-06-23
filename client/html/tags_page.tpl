<div class='tag-list'>
    <% if (ctx.results.length) { %>
        <table>
            <thead>
                <th class='names'>
                    <% if (ctx.query == 'sort:name' || !ctx.query) { %>
                        <a href='/tags/text=-sort:name'>Tag name(s)</a>
                    <% } else { %>
                        <a href='/tags/text=sort:name'>Tag name(s)</a>
                    <% } %>
                </th>
                <th class='implications'>
                    <% if (ctx.query == 'sort:implication-count') { %>
                        <a href='/tags/text=-sort:implication-count'>Implications</a>
                    <% } else { %>
                        <a href='/tags/text=sort:implication-count'>Implications</a>
                    <% } %>
                </th>
                <th class='suggestions'>
                    <% if (ctx.query == 'sort:suggestion-count') { %>
                        <a href='/tags/text=-sort:suggestion-count'>Suggestions</a>
                    <% } else { %>
                        <a href='/tags/text=sort:suggestion-count'>Suggestions</a>
                    <% } %>
                </th>
                <th class='usages'>
                    <% if (ctx.query == 'sort:usages') { %>
                        <a href='/tags/text=-sort:usages'>Usages</a>
                    <% } else { %>
                        <a href='/tags/text=sort:usages'>Usages</a>
                    <% } %>
                </th>
                <th class='edit-time'>
                    <% if (ctx.query == 'sort:last-edit-time') { %>
                        <a href='/tags/text=-sort:last-edit-time'>Edit time</a>
                    <% } else { %>
                        <a href='/tags/text=sort:last-edit-time'>Edit time</a>
                    <% } %>
                </th>
            </thead>
            <tbody>
                <% for (let tag of ctx.results) { %>
                    <tr>
                        <td class='names'>
                            <ul>
                                <% for (let name of tag.names) { %>
                                    <li><%= ctx.makeTagLink(name) %></li>
                                <% } %>
                            </ul>
                        </td>
                        <td class='implications'>
                            <% if (tag.implications.length) { %>
                                <ul>
                                    <% for (let name of tag.implications) { %>
                                        <li><%= ctx.makeTagLink(name) %></li>
                                    <% } %>
                                </ul>
                            <% } else { %>
                                -
                            <% } %>
                        </td>
                        <td class='suggestions'>
                            <% if (tag.suggestions.length) { %>
                                <ul>
                                    <% for (let name of tag.suggestions) { %>
                                        <li><%= ctx.makeTagLink(name) %></li>
                                    <% } %>
                                </ul>
                            <% } else { %>
                                -
                            <% } %>
                        </td>
                        <td class='usages'>
                            <%- tag.postCount %>
                        </td>
                        <td class='edit-time'>
                            <%= ctx.makeRelativeTime(tag.lastEditTime) %>
                        </td>
                    </tr>
                <% } %>
            </tbody>
        </table>
    <% } %>
</div>
