<div class='content-wrapper' id='login'>
    <h1>Log in</h1>
    <form>
        <div class='input'>
            <ul>
                <li>
                    <%= ctx.makeTextInput({text: 'User name', id: 'user-name', name: 'name', required: true, pattern: ctx.userNamePattern}) %>
                </li>
                <li>
                    <%= ctx.makePasswordInput({text: 'Password', id: 'user-password', name: 'password', required: true, pattern: ctx.passwordPattern}) %>
                </li>
                <li>
                    <%= ctx.makeCheckbox({text: 'Remember me', id: 'remember-user', name: 'remember-user'}) %>
                </li>
            </ul>
        </div>
        <div class='messages'></div>
        <div class='buttons'>
            <input type='submit' value='Log in'/>
            <% if (ctx.canSendMails) { %>
                <a class='append' href='/password-reset'>Forgot the password?</a>
            <% } %>
        </div>
    </form>
</div>
