<div id='user-delete'>
    <form>
        <ul class='input'>
            <li>
                <%= ctx.makeCheckbox({
                    name: 'confirm-deletion',
                    text: 'I confirm that I want to delete this account.',
                    required: true,
                }) %>
            </li>
        </ul>

        <div class='messages'></div>
        <div class='buttons'>
            <input type='submit' value='Delete account'/>
        </div>
    </form>
</div>
