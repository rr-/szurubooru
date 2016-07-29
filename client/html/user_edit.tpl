<div id='user-edit'>
    <form>
        <ul>
            <% if (ctx.canEditName) { %>
                <li>
                    <%= ctx.makeTextInput({text: 'User name', id: 'user-name', name: 'name', value: ctx.user.name, pattern: ctx.userNamePattern}) %>
                </li>
            <% } %>

            <% if (ctx.canEditPassword) { %>
                <li>
                    <%= ctx.makePasswordInput({text: 'Password', id: 'user-password', name: 'password', placeholder: 'leave blank if not changing', pattern: ctx.passwordPattern}) %>
                </li>
            <% } %>

            <% if (ctx.canEditEmail) { %>
                <li>
                    <%= ctx.makeEmailInput({text: 'Email', id: 'user-email', name: 'email', value: ctx.user.email}) %>
                </li>
            <% } %>

            <% if (ctx.canEditRank) { %>
                <li>
                    <%= ctx.makeSelect({text: 'Rank', id: 'user-rank', name: 'rank', keyValues: ctx.ranks, selectedKey: ctx.user.rank}) %>
                </li>
            <% } %>

            <% if (ctx.canEditAvatar) { %>
                <li class='avatar'>
                    <label>Avatar</label>
                    <div id='avatar-content'></div>
                    <div id='avatar-radio'>
                        <%= ctx.makeRadio({text: 'Gravatar', id: 'gravatar-radio', name: 'avatar-style', value: 'gravatar', selectedValue: ctx.user.avatarStyle}) %>
                        <%= ctx.makeRadio({text: 'Manual avatar', id: 'avatar-radio', name: 'avatar-style', value: 'manual', selectedValue: ctx.user.avatarStyle}) %>
                    </div>
                </li>
            <% } %>
        </ul>

        <div class='messages'></div>

        <div class='buttons'>
            <input type='submit' value='Save settings'/>
        </div>
    </form>
</div>
