<div class='content-wrapper' id='password-reset'>
    <h1>Password reset</h1>
    <form autocomplete='off'>
        <div class='input'>
            <ul>
                <li>
                    <%= ctx.makeTextInput({
                        text: 'User name or e-mail address',
                        name: 'user-name',
                        required: true,
                    }) %>
                </li>
            </ul>
        </div>
        <p><small>Proceeding will send an e-mail that contains a password reset
        link. Clicking it is going to generate a new password for your account.
        It is recommended to change that password to something else.</small></p>
        <div class='messages'></div>
        <div class='buttons'>
            <input type='submit' value='Proceed'/>
        </div>
    </form>
</div>
