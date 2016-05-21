<div class='tag-list-header'>
    <form class='horizontal'>
        <div class='input'>
            <ul>
                <li>
                    <%= ctx.makeTextInput({id: 'search-text', name: 'search-text', value: ctx.searchQuery.text}) %>
                </li>
            </ul>
        </div>
        <div class='buttons'>
            <input type='submit' value='Search'/>
            <a class='button append' href='/help/search/tags'>Syntax help</a>
            <% if (ctx.canEditTagCategories) { %>
                <a class='append' href='/tag-categories'>Tag categories</a>
            <% } %>
        </div>
    </form>
</div>
