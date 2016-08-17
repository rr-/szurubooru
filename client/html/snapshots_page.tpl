<div class='snapshot-list'>
    <% if (ctx.results.length) { %>
        <ul>
            <% for (let item of ctx.results) { %>
                <li>
                    <div class='header operation-<%= item.operation %>'>
                        <span class='time'>
                            <%= ctx.makeRelativeTime(item.time) %>
                        </span>

                        <%= ctx.makeUserLink(item.user) %>

                        <%= item.operation %>

                        <%= ctx.makeResourceLink(item.type, item.id) %>
                    </div>

                    <div class='details'><!--
                        --><% if (item.operation === 'created') { %><!--
                            --><%= ctx.makeItemCreation(item.type, item.data) %><!--
                        --><% } else if (item.operation === 'modified') { %><!--
                            --><%= ctx.makeItemModification(item.type, item.data) %><!--
                        --><% } else if (item.operation === 'merged') { %><!--
                            -->Merged to <%= ctx.makeResourceLink(item.data[0], item.data[1]) %><!--
                        --><% } %><!--
                    --></div>
                </li>
            <% } %>
        </ul>
    <% } %>
</div>
