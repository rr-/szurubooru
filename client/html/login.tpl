<div class='content-wrapper' id='login'>
    <h1>Log in</h1>
    <form>
        <div class='input'>
            <ul>
                <li>
                    <%= makeTextInput({text: 'User name', id: 'user-name', name: 'name', required: true, pattern: userNamePattern}) %>
                </li>
                <li>
                    <%= makePasswordInput({text: 'Password', id: 'user-password', name: 'password', required: true, pattern: passwordPattern}) %>
                </li>
                <li>
                    <%= makeCheckbox({text: 'Remember me', id: 'remember-user', name: 'remember-user'}) %>
                </li>
            </ul>
        </div>
        <div class='messages'></div>
        <div class='buttons'>
            <input type='submit' value='Log in'/>
            <% if (canSendMails) { %>
                <a class='append' href='/password-reset'>Forgot the password?</a>
            <% } %>
        </div>
    </form>
</div>
