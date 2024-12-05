<%
function kebabToTitleCase(str) {
    return str
        .split('-') // Split the string into words using the hyphen as the delimiter
        .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()) // Capitalize the first letter of each word
        .join(' '); // Join the words back together with spaces
}
%>

<div class='pools-grid'>
    <% if (ctx.response.results.length) { %>
        <% for (const pool of ctx.response.results) { %>
            <a href='<%- ctx.formatClientLink('posts', {query: 'pool:' + pool.id}) %>'>
                <div class='pool'>
                    <div class='pool-inner-grid'>
                        <% let counter = 0 %>
                        <% for (const post of pool.posts) { %>
                            <div><img src='<%- post.thumbnailUrl %>' /></div>
                            <% counter++ %>
                            <% if (counter === 4) break %>
                        <% } %>
                        <div class='pool-title'><%= kebabToTitleCase(pool.names[0]) %> (<%- pool.postCount %>)</div>
                    </div>
                </div>
            </a>
        <% } %>
    <% } else { %>
        <p>No pools to display.</p>
    <% } %>
</div>

<div class='pool-list table-wrap'>
    <% if (ctx.response.results.length) { %>
        <table>
            <thead>
                <th class='names'>
                    <% if (ctx.parameters.query == 'sort:name' || !ctx.parameters.query) { %>
                        <a href='<%- ctx.formatClientLink('pools', {query: '-sort:name'}) %>'>Pool name(s)</a>
                    <% } else { %>
                        <a href='<%- ctx.formatClientLink('pools', {query: 'sort:name'}) %>'>Pool name(s)</a>
                    <% } %>
                </th>
                <th class='post-count'>
                     <% if (ctx.parameters.query == 'sort:post-count') { %>
                        <a href='<%- ctx.formatClientLink('pools', {query: '-sort:post-count'}) %>'>Post count</a>
                     <% } else { %>
                        <a href='<%- ctx.formatClientLink('pools', {query: 'sort:post-count'}) %>'>Post count</a>
                     <% } %>
                     </th>
                <th class='creation-time'>
                    <% if (ctx.parameters.query == 'sort:creation-time') { %>
                        <a href='<%- ctx.formatClientLink('pools', {query: '-sort:creation-time'}) %>'>Created on</a>
                    <% } else { %>
                        <a href='<%- ctx.formatClientLink('pools', {query: 'sort:creation-time'}) %>'>Created on</a>
                    <% } %>
                </th>
            </thead>
            <tbody>
                <% for (let pool of ctx.response.results) { %>
                    <tr>
                        <td class='names'>
                            <ul>
                                <% for (let name of pool.names) { %>
                                    <li><%= ctx.makePoolLink(pool.id, false, false, pool, name) %></li>
                                <% } %>
                            </ul>
                        </td>
                        <td class='post-count'>
                            <a href='<%- ctx.formatClientLink('posts', {query: 'pool:' + pool.id}) %>'><%- pool.postCount %></a>
                        </td>
                        <td class='creation-time'>
                            <%= ctx.makeRelativeTime(pool.creationTime) %>
                        </td>
                    </tr>
                <% } %>
            </tbody>
        </table>
    <% } %>
</div>
