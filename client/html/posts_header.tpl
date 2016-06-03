<div class='post-list-header'>
    <form class='horizontal'>
        <div class='input'>
            <ul>
                <li>
                    <%= ctx.makeTextInput({id: 'search-text', name: 'search-text', value: ctx.searchQuery.text}) %>
                </li>
            </ul>
        </div>
        <div class='buttons'>
            <input class='mousetrap' type='submit' value='Search'/>
            <input data-safety=safe type='button' class='mousetrap safety safety-safe <%= ctx.settings.listPosts.safe ? '' : 'disabled' %>'/>
            <input data-safety=sketchy type='button' class='mousetrap safety safety-sketchy <%= ctx.settings.listPosts.sketchy ? '' : 'disabled' %>'/>
            <input data-safety=unsafe type='button' class='mousetrap safety safety-unsafe <%= ctx.settings.listPosts.unsafe ? '' : 'disabled' %>'/>
            <a class='mousetrap button append' href='/help/search/posts'>Syntax help</a>
        </div>
    </form>
</div>
