<div id='user-edit'>
    <form class='tabular'>
        <div class='left'>
            <div class='input'>
                <ul>
                    <% if (canEditName) { %>
                        <li>
                            <%= makeTextInput({text: 'User name', id: 'user-name', name: 'name', value: user.name, pattern: userNamePattern}) %>
                        </li>
                    <% } %>

                    <% if (canEditPassword) { %>
                        <li>
                            <%= makePasswordInput({text: 'Password', id: 'user-password', name: 'password', placeholder: 'leave blank if not changing', pattern: passwordPattern}) %>
                        </li>
                    <% } %>

                    <% if (canEditEmail) { %>
                        <li>
                            <%= makeEmailInput({text: 'Email', id: 'user-email', name: 'email', value: user.email}) %>
                        </li>
                    <% } %>

                    <% if (canEditRank) { %>
                        <li>
                            <%= makeSelect({text: 'Rank', id: 'user-rank', name: 'rank', keyValues: ranks, selectedKey: user.rank}) %>
                        </li>
                    <% } %>
                </ul>
            </div>
            <div class='messages'></div>
            <div class='buttons'>
                <input type='submit' value='Save settings'/>
            </div>
        </div>

        <% if (canEditAvatar) { %>
            <div class='right'>
                <ul>
                    <li>
                        <%= makeRadio({text: 'Gravatar', id: 'gravatar-radio', name: 'avatar-style', value: 'gravatar', selectedValue: user.avatarStyle}) %>
                    </li>
                    <li>
                        <%= makeRadio({text: 'Manual avatar', id: 'avatar-radio', name: 'avatar-style', value: 'manual', selectedValue: user.avatarStyle}) %>
                        <div id='avatar-content'></div>
                    </li>
                </ul>
            </div>
        <% } %>
    </form>
</div>
