<div class='post-list-header'>
    <form class='horizontal search'>
        <%= ctx.makeTextInput({text: 'Search query', id: 'search-text', name: 'search-text', value: ctx.searchQuery.text}) %>
        <input class='mousetrap' type='submit' value='Search'/>
        <input data-safety=safe type='button' class='mousetrap safety safety-safe <%- ctx.settings.listPosts.safe ? '' : 'disabled' %>'/>
        <input data-safety=sketchy type='button' class='mousetrap safety safety-sketchy <%- ctx.settings.listPosts.sketchy ? '' : 'disabled' %>'/>
        <input data-safety=unsafe type='button' class='mousetrap safety safety-unsafe <%- ctx.settings.listPosts.unsafe ? '' : 'disabled' %>'/>
        <a class='mousetrap button append' href='/help/search/posts'>Syntax help</a>
    </form>
    <% if (ctx.canMassTag) { %>
        <form class='masstag horizontal'>
            <% if (ctx.searchQuery.tag) { %>
                <span class='append'>Tagging with:</span>
            <% } else { %>
                <a class='mousetrap button append open-masstag' href='#'>Mass tag</a>
            <% } %>
            <%= ctx.makeTextInput({name: 'masstag', value: ctx.searchQuery.tag}) %>
            <input class='mousetrap start-tagging' type='submit' value='Start tagging'/>
            <a class='mousetrap button append stop-tagging' href='#'>Stop tagging</a>
        </form>
    <% } %>
</div>
