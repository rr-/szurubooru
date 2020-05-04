<div class='content-wrapper pool-create'>
    <form>
        <ul class='input'>
            <li class='names'>
                <%= ctx.makeTextInput({
                    text: 'Names',
                    value: '',
                    required: true,
                }) %>
            </li>
            <li class='category'>
                <%= ctx.makeSelect({
                    text: 'Category',
                    keyValues: ctx.categories,
                    selectedKey: 'default',
                    required: true,
                }) %>
            </li>
            <li class='description'>
                <%= ctx.makeTextarea({
                    text: 'Description',
                    value: '',
                }) %>
            </li>
            <li class='posts'>
                <%= ctx.makeTextInput({
                    text: 'Posts',
                    value: '',
                    placeholder: 'space-separated post IDs',
                }) %>
            </li>
        </ul>

        <% if (ctx.canCreate) { %>
            <div class='messages'></div>

            <div class='buttons'>
                <input type='submit' class='save' value='Create pool'>
            </div>
        <% } %>
    </form>
</div>
