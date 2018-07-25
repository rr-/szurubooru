<div class='user-list-header'>
    <form class='horizontal'>
        <ul class='input'>
            <li>
                <%= ctx.makeTextInput({text: 'Search query', id: 'search-text', name: 'search-text', value: ctx.parameters.query}) %>
            </li>
        </ul>

        <div class='buttons'>
            <input type='submit' value='Search'/>
            <a class='append' href='<%- ctx.formatClientLink('help', 'search', 'users') %>'>Syntax help</a>
        </div>
    </form>
</div>
