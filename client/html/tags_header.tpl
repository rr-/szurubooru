<div class='tag-list-header'>
    <form class='horizontal'>
        <ul class='input'>
            <li>
                <%= ctx.makeTextInput({text: 'Search query', id: 'search-text', name: 'search-text', value: ctx.parameters.query}) %>
            </li>
        </ul>

        <div class='buttons'>
            <input type='submit' value='Search'/>
            <a class='button append' href='<%- ctx.formatClientLink('help', 'search', 'tags') %>'>Syntax help</a>
            <% if (ctx.canEditTagCategories) { %>
                <a class='append' href='<%- ctx.formatClientLink('tag-categories') %>'>Tag categories</a>
            <% } %>
        </div>
    </form>
</div>
