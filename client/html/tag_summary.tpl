<div class='content-wrapper tag-summary'>
    <form class='tabular'>
        <div class='input'>
            <ul>
                <li class='names'>
                    <%= ctx.makeTextInput({text: 'Names', value: ctx.tag.names.join(' '), required: true, readonly: !ctx.canEditNames, pattern: ctx.tagNamesPattern}) %>
                </li>
                <li class='category'>
                    <%= ctx.makeSelect({text: 'Category', keyValues: ctx.categories, selectedKey: ctx.tag.category, required: true, readonly: !ctx.canEditCategory}) %>
                </li>
                <li class='implications'>
                    <%= ctx.makeTextInput({text: 'Implications', value: ctx.tag.implications.join(' '), readonly: !ctx.canEditImplications}) %>
                </li>
                <li class='suggestions'>
                    <%= ctx.makeTextInput({text: 'Suggestions', value: ctx.tag.suggestions.join(' '), readonly: !ctx.canEditSuggestions}) %>
                </li>
            </ul>
        </div>
        <% if (ctx.canEditNames || ctx.canEditCategory || ctx.canEditImplications || ctx.canEditSuggestions) { %>
            <div class='messages'></div>
            <div class='buttons'>
                <input type='submit' class='save' value='Save changes'>
            </div>
        <% } %>
    </form>
</div>
