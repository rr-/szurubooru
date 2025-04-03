<div class='pool-delete'>
    <form>
        <p>This pool has <a href='<%- ctx.formatClientLink('posts', {query: 'pool:' + ctx.pool.id + ' -sort:pool'}) %>'><%- ctx.pool.postCount %> post(s)</a>.</p>

        <ul class='input'>
            <li>
                <%= ctx.makeCheckbox({
                    name: 'confirm-deletion',
                    text: 'I confirm that I want to delete this pool.',
                    required: true,
                }) %>
            </li>
        </ul>

        <div class='messages'></div>

        <div class='buttons'>
            <input type='submit' value='Delete pool'/>
        </div>
    </form>
</div>
