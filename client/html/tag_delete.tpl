<div class='tag-delete'>
    <form>
        <p>This tag has <a href='/posts/query=<%- encodeURIComponent(ctx.tag.names[0]) %>'><%- ctx.tag.postCount %> usage(s)</a>.</p>

        <ul>
            <li>
                <%= ctx.makeCheckbox({id: 'confirm-deletion', name: 'confirm-deletion', required: true, text: 'I confirm that I want to delete this tag.'}) %>
            </li>
        </ul>

        <div class='messages'></div>

        <div class='buttons'>
            <input type='submit' value='Delete tag'/>
        </div>
    </form>
</div>
