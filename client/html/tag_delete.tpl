<div class='tag-delete'>
    <form>
        <p>This tag has <a href='<%- ctx.formatClientLink('posts', {query: ctx.escapeColons(ctx.tag.names[0])}) %>'><%- ctx.tag.postCount %> usage(s)</a>.</p>

        <ul class='input'>
            <li>
                <%= ctx.makeCheckbox({
                    name: 'confirm-deletion',
                    text: 'I confirm that I want to delete this tag.',
                    required: true,
                }) %>
            </li>
        </ul>

        <div class='messages'></div>

        <div class='buttons'>
            <input type='submit' value='Delete tag'/>
        </div>
    </form>
</div>
