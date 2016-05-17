<nav class='text-nav'>
    <ul>
        <li>
            <% if (prevLinkActive) { %>
                <a class='prev' href='<%= prevLink %>'>
            <% } else { %>
                <a class='prev disabled'>
            <% } %>
                <i class='fa fa-chevron-left'></i>
                <span>Previous page</span>
            </a>
        </li>

        <% _.each(pages, page => { %>
            <% if (page.ellipsis) { %>
                <li>&hellip;</li>
            <% } else { %>
                <% if (page.active) { %>
                    <li class='active'>
                <% } else { %>
                    <li>
                <% } %>
                    <a href='<%= page.link %>'><%= page.number %></a>
                </li>
            <% } %>
        <% }) %>

        <li>
            <% if (nextLinkActive) { %>
                <a class='next' href='<%= nextLink %>'>
            <% } else { %>
                <a class='next disabled'>
            <% } %>
                <i class='fa fa-chevron-right'></i>
                <span>Next page</span>
            </a>
        </li>
    </ul>
</nav>
