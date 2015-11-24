<div id="user-view" class="tab-wrapper">
    <div class="messages"></div>

    <div class="top">
        <div class="side">
            <img width="100" height="100" src="/data/thumbnails/100x100/avatars/<%= user.name %>" alt="Avatar"/>
            <br/>
            <%= user.name %>
        </div>

        <% if ((canChangeBrowsingSettings || canChangeAccountSettings || canDeleteAccount)) { %>

        <ul>
            <li>
                <a class="big-button" href="#/user/<%= user.name %>" data-tab="basic-info">
                    Basic information
                </a>
            </li>

            <% if (canChangeBrowsingSettings) { %>
                <li>
                    <a class="big-button" href="#/user/<%= user.name %>/browsing-settings" data-tab="browsing-settings">
                        Browsing settings
                    </a>
                </li>
            <% } %>

            <% if (canChangeAccountSettings) { %>
                <li>
                    <a class="big-button" href="#/user/<%= user.name %>/account-settings" data-tab="account-settings">
                        Account settings
                    </a>
                </li>
            <% } %>

            <% if (canDeleteAccount) { %>
                <li>
                    <a class="big-button" href="#/user/<%= user.name %>/account-removal" data-tab="account-removal">
                        Account removal
                    </a>
                </li>
            <% } %>
        </ul>

        <% } %>
    </div>

    <div class="tab basic-info" data-tab="basic-info">
        <h2>Basic information</h2>

        <table>
            <tr>
                <td>Registered:</td>
                <td title="<%= util.formatAbsoluteTime(user.creationTime) %>">
                    <%= util.formatRelativeTime(user.creationTime) %>
                </td>
            </tr>

            <tr>
                <td>Seen:</td>
                <td title="<%= util.formatAbsoluteTime(user.lastLoginTime) %>">
                    <%= util.formatRelativeTime(user.lastLoginTime) %>
                </td>
            </tr>

            <% if (user.accessRank) { %>
                <tr>
                    <td>Access rank:</td>
                    <%
                        var accessRanks = {
                            anonymous: 'anonymous',
                            restrictedUser: 'restricted user',
                            regularUser: 'regular user',
                            powerUser: 'power user',
                            moderator: 'moderator',
                            administrator: 'administrator'
                        };
                    %>
                    <td><%= accessRanks[user.accessRank] %></td>
                </tr>
            <% } %>

            <tr>
                <td>Quick links:</td>
                <td>
                    <ul class="links">
                        <li>
                            <a href="#/posts/query=fav:<%= user.name %>">
                                Favorites
                            </a>
                        </li>

                        <li>
                            <a href="#/posts/query=uploader:<%= user.name %>">
                                Uploads
                            </a>
                        </li>

                        <% if (isLoggedIn) { %>
                            <li>
                                <a href="#/posts/query=special:liked">
                                    Upvoted posts
                                </a>
                            </li>

                            <li>
                                <a href="#/posts/query=special:disliked">
                                    Downvoted posts
                                </a>
                            </li>
                        <% } %>
                    </ul>
                </td>
            </tr>
        </table>
    </div>

    <% if (canChangeBrowsingSettings) { %>
        <div class="tab" data-tab="browsing-settings">
            <h2>Browsing settings</h2>
            <div id="browsing-settings-target"></div>
        </div>
    <% } %>

    <% if (canChangeAccountSettings) { %>
        <div class="tab" data-tab="account-settings">
            <h2>Account settings</h2>
            <div id="account-settings-target"></div>
        </div>
    <% } %>

    <% if (canDeleteAccount) { %>
        <div class="tab" data-tab="account-removal">
            <h2>Account removal</h2>
            <div id="account-removal-target"></div>
        </div>
    <% } %>

</div>
