<div id='user-edit'>
    <form class='tabular'>
        <div class='left'>
            <div class='input'>
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
                </ul>
            </div>
            <div class='messages'></div>
            <div class='buttons'>
                <input type='submit' value='Save settings'/>
            </div>
        </div>

        <% if (ctx.canEditAvatar) { %>
            <div class='right'>
                <ul>
                    <li>
                        <%= ctx.makeRadio({text: 'Gravatar', id: 'gravatar-radio', name: 'avatar-style', value: 'gravatar', selectedValue: ctx.user.avatarStyle}) %>
                    </li>
                    <li>
                        <%= ctx.makeRadio({text: 'Manual avatar', id: 'avatar-radio', name: 'avatar-style', value: 'manual', selectedValue: ctx.user.avatarStyle}) %>
                        <div id='avatar-content'></div>
                    </li>
                </ul>
            </div>
        <% } %>
    </form>
</div>
