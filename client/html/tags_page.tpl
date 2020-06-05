<div class='tag-list table-wrap'>
    <% if (ctx.response.results.length) { %>
        <table>
            <thead>
                <th class='names'>
                    <% if (ctx.parameters.query == 'sort:name' || !ctx.parameters.query) { %>
                        <a href='<%- ctx.formatClientLink('tags', {query: '-sort:name'}) %>'>Tag name(s)</a>
                    <% } else { %>
                        <a href='<%- ctx.formatClientLink('tags', {query: 'sort:name'}) %>'>Tag name(s)</a>
                    <% } %>
                </th>
                <th class='implications'>
                    <% if (ctx.parameters.query == 'sort:implication-count') { %>
                        <a href='<%- ctx.formatClientLink('tags', {query: '-sort:implication-count'}) %>'>Implications</a>
                    <% } else { %>
                        <a href='<%- ctx.formatClientLink('tags', {query: 'sort:implication-count'}) %>'>Implications</a>
                    <% } %>
                </th>
                <th class='suggestions'>
                    <% if (ctx.parameters.query == 'sort:suggestion-count') { %>
                        <a href='<%- ctx.formatClientLink('tags', {query: '-sort:suggestion-count'}) %>'>Suggestions</a>
                    <% } else { %>
                        <a href='<%- ctx.formatClientLink('tags', {query: 'sort:suggestion-count'}) %>'>Suggestions</a>
                    <% } %>
                </th>
                <th class='usages'>
                    <% if (ctx.parameters.query == 'sort:usages') { %>
                        <a href='<%- ctx.formatClientLink('tags', {query: '-sort:usages'}) %>'>Usages</a>
                    <% } else { %>
                        <a href='<%- ctx.formatClientLink('tags', {query: 'sort:usages'}) %>'>Usages</a>
                    <% } %>
                </th>
                <th class='creation-time'>
                    <% if (ctx.parameters.query == 'sort:creation-time') { %>
                        <a href='<%- ctx.formatClientLink('tags', {query: '-sort:creation-time'}) %>'>Created on</a>
                    <% } else { %>
                        <a href='<%- ctx.formatClientLink('tags', {query: 'sort:creation-time'}) %>'>Created on</a>
                    <% } %>
                </th>
            </thead>
            <tbody>
                <% for (let tag of ctx.response.results) { %>
                    <tr>
                        <td class='names'>
                            <ul>
                                <% for (let name of tag.names) { %>
                                    <li><%= ctx.makeTagLink(name, false, false, tag) %></li>
                                <% } %>
                            </ul>
                        </td>
                        <td class='implications'>
                            <% if (tag.implications.length) { %>
                                <ul>
                                    <% for (let relation of tag.implications) { %>
                                        <li><%= ctx.makeTagLink(relation.names[0], false, false, relation) %></li>
                                    <% } %>
                                </ul>
                            <% } else { %>
                                -
                            <% } %>
                        </td>
                        <td class='suggestions'>
                            <% if (tag.suggestions.length) { %>
                                <ul>
                                    <% for (let relation of tag.suggestions) { %>
                                        <li><%= ctx.makeTagLink(relation.names[0], false, false, relation) %></li>
                                    <% } %>
                                </ul>
                            <% } else { %>
                                -
                            <% } %>
                        </td>
                        <td class='usages'>
                            <%- tag.postCount %>
                        </td>
                        <td class='creation-time'>
                            <%= ctx.makeRelativeTime(tag.creationTime) %>
                        </td>
                    </tr>
                <% } %>
            </tbody>
        </table>
    <% } %>
</div>
