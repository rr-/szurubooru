<div class='content-wrapper' id='login'>
    <h1>Log in</h1>
    <% if (!ctx.disablePasswordAuth) { %>
        <form>
            <ul class='input'>
                <li>
                    <%= ctx.makeTextInput({
                        text: 'User name',
                        name: 'name',
                        required: true,
                        pattern: ctx.userNamePattern,
                    }) %>
                </li>
                <li>
                    <%= ctx.makePasswordInput({
                        text: 'Password',
                        name: 'password',
                        required: true,
                        pattern: ctx.passwordPattern,
                    }) %>
                </li>
                <li>
                    <%= ctx.makeCheckbox({
                        text: 'Remember me',
                        name: 'remember-user',
                    }) %>
                </li>
            </ul>

            <div class='messages'></div>

            <div class='buttons'>
                <input type='submit' value='Log in'/>
                <a class='append' href='<%- ctx.formatClientLink('password-reset') %>'>Forgot the password?</a>
            </div>
        </form>
        <% if (ctx.oidcEnabled) { %>
            <div class='oidc-login'>
                <p class='append'>- or -</p>
                <button type='button' class='oidc-login-btn'>
                    <% if (ctx.oidcButtonIcon) { %><img src='<%- ctx.oidcButtonIcon %>' alt='' class='oidc-icon'/><% } %>
                    <%- ctx.oidcButtonLabel %>
                </button>
            </div>
        <% } %>
    <% } else if (ctx.oidcEnabled) { %>
        <div class='oidc-login'>
            <button type='button' class='oidc-login-btn'>
                <% if (ctx.oidcButtonIcon) { %><img src='<%- ctx.oidcButtonIcon %>' alt='' class='oidc-icon'/><% } %>
                <%- ctx.oidcButtonLabel %>
            </button>
        </div>
    <% } %>
</div>
