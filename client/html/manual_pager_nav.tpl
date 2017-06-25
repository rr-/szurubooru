<nav class='buttons'>
    <ul>
        <li>
            <% if (ctx.prevPage !== ctx.currentPage) { %>
                <a rel='prev' class='prev' href='<%- ctx.getClientUrlForPage(ctx.pages.get(ctx.prevPage).offset, ctx.pages.get(ctx.prevPage).limit) %>'>
            <% } else { %>
                <a rel='prev' class='prev disabled'>
            <% } %>
                <i class='fa fa-chevron-left'></i>
                <span class='vim-nav-hint'>&lt; Previous page</span>
            </a>
        </li>

        <% for (let page of ctx.pages.values()) { %>
            <% if (page.ellipsis) { %>
                <li>&hellip;</li>
            <% } else { %>
                <% if (page.active) { %>
                    <li class='active'>
                <% } else { %>
                    <li>
                <% } %>
                    <a href='<%- ctx.getClientUrlForPage(page.offset, page.limit) %>'><%- page.number %></a>
                </li>
            <% } %>
        <% } %>

        <li>
            <% if (ctx.nextPage !== ctx.currentPage) { %>
                <a rel='next' class='next' href='<%- ctx.getClientUrlForPage(ctx.pages.get(ctx.nextPage).offset, ctx.pages.get(ctx.nextPage).limit) %>'>
            <% } else { %>
                <a rel='next' class='next disabled'>
            <% } %>
                <i class='fa fa-chevron-right'></i>
                <span class='vim-nav-hint'>Next page &gt;</span>
            </a>
        </li>
    </ul>
</nav>
