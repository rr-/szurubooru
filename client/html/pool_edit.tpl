<div class='content-wrapper pool-edit'>
    <form>
        <ul class='input'>
            <li class='names'>
                <% if (ctx.canEditNames) { %>
                    <%= ctx.makeTextInput({
                        text: 'Names',
                        value: ctx.pool.names.join(' '),
                        required: true,
                    }) %>
                <% } %>
            </li>
            <li class='category'>
                <% if (ctx.canEditCategory) { %>
                    <%= ctx.makeSelect({
                        text: 'Category',
                        keyValues: ctx.categories,
                        selectedKey: ctx.pool.category,
                        required: true,
                    }) %>
                <% } %>
            </li>
            <li class='description'>
                <% if (ctx.canEditDescription) { %>
                    <%= ctx.makeTextarea({
                        text: 'Description',
                        value: ctx.pool.description,
                    }) %>
                <% } %>
            </li>
            <li class='posts'>
                <% if (ctx.canEditPosts) { %>
                    <%= ctx.makeTextInput({
                        text: 'Posts',
                        placeholder: 'space-separated post IDs',
                        value: ctx.pool.posts.map(post => post.id).join(' ')
                    }) %>
                <% } %>
            </li>
        </ul>

        <% if (ctx.canEditAnything) { %>
            <div class='messages'></div>

            <div class='buttons'>
                <input type='submit' class='save' value='Save changes'>
            </div>
        <% } %>
    </form>
</div>
