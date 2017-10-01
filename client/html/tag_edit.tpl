<div class='content-wrapper tag-edit'>
    <form>
        <ul class='input'>
            <li class='names'>
                <% if (ctx.canEditNames) { %>
                    <%= ctx.makeTextInput({
                        text: 'Names',
                        value: ctx.tag.names.join(' '),
                        required: true,
                    }) %>
                <% } %>
            </li>
            <li class='category'>
                <% if (ctx.canEditCategory) { %>
                    <%= ctx.makeSelect({
                        text: 'Category',
                        keyValues: ctx.categories,
                        selectedKey: ctx.tag.category,
                        required: true,
                    }) %>
                <% } %>
            </li>
            <li class='implications'>
                <% if (ctx.canEditImplications) { %>
                    <%= ctx.makeTextInput({text: 'Implications'}) %>
                <% } %>
            </li>
            <li class='suggestions'>
                <% if (ctx.canEditSuggestions) { %>
                    <%= ctx.makeTextInput({text: 'Suggestions'}) %>
                <% } %>
            </li>
            <li class='description'>
                <% if (ctx.canEditDescription) { %>
                    <%= ctx.makeTextarea({
                        text: 'Description',
                        value: ctx.tag.description,
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
