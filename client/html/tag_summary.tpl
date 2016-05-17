<div class='content-wrapper tag-summary'>
    <form class='tabular'>
        <div class='input'>
            <ul>
                <li class='names'>
                    <%= makeTextInput({text: 'Names', value: tag.names.join(' '), required: true, readonly: !canEditNames, pattern: tagNamesPattern}) %>
                </li>
                <li class='category'>
                    <%= makeSelect({text: 'Category', keyValues: categories, selectedKey: tag.category, required: true, readonly: !canEditCategory}) %>
                </li>
                <li class='implications'>
                    <%= makeTextInput({text: 'Implications', value: tag.implications.join(' '), readonly: !canEditImplications}) %>
                </li>
                <li class='suggestions'>
                    <%= makeTextInput({text: 'Suggestions', value: tag.suggestions.join(' '), readonly: !canEditSuggestions}) %>
                </li>
            </ul>
        </div>
        <% if (canEditNames || canEditCategory || canEditImplications || canEditSuggestions) { %>
            <div class='messages'></div>
            <div class='buttons'>
                <input type='submit' class='save' value='Save changes'>
            </div>
        <% } %>
    </form>
</div>
