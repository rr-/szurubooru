<div id='user-tokens'>
    <div class='messages'></div>
    <% if (ctx.tokens.length > 0) { %>
    <div class='token-flex-container'>
        <% _.each(ctx.tokens, function(token, index) { %>
        <div class='token-flex-row'>
            <div class='token-flex-column token-flex-labels'>
                <div class='token-flex-row'>Token:</div>
                <div class='token-flex-row'>Note:</div>
                <div class='token-flex-row'>Created:</div>
                <div class='token-flex-row'>Expires:</div>
                <div class='token-flex-row no-wrap'>Last used:</div>
            </div>
            <div class='token-flex-column full-width'>
                <div class='token-flex-row'><%= token.token %></div>
                <div class='token-flex-row'>
                    <% if (token.note !== null) { %>
                        <%= token.note %>
                    <% } else { %>
                        No note
                    <% } %>
                    <a class='token-change-note' data-token-id='<%= index %>' href='#'>(change)</a>
                </div>
                <div class='token-flex-row'><%= ctx.makeRelativeTime(token.creationTime) %></div>
                <div class='token-flex-row'>
                    <% if (token.expirationTime) { %>
                        <%= ctx.makeRelativeTime(token.expirationTime) %>
                    <% } else { %>
                        No expiration
                    <% } %>
                </div>
                <div class='token-flex-row'><%= ctx.makeRelativeTime(token.lastUsageTime) %></div>
            </div>
        </div>
        <div class='token-flex-row'>
            <div class='token-flex-column full-width'>
                <div class='token-flex-row'>
                    <form class='token' data-token-id='<%= index %>'>
                        <% if (token.isCurrentAuthToken) { %>
                            <input type='submit' value='Delete and logout'
                                title='This token is used to authenticate this client, deleting it will force a logout.'/>
                        <% } else { %>
                            <input type='submit' value='Delete'/>
                        <% } %>
                    </form>
                </div>
            </div>
        </div>
        <hr/>
        <% }); %>
    </div>
    <% } else { %>
        <h2>No Registered Tokens</h2>
    <% } %>
    <form id='create-token-form'>
        <ul class='input'>
            <li class='note'>
                <%= ctx.makeTextInput({
                    text: 'Note',
                    id: 'note',
                }) %>
            </li>
            <li class='expirationTime'>
                <%= ctx.makeDateInput({
                    text: 'Expires',
                    id: 'expirationTime',
                }) %>
            </li>
        </ul>
        <div class='buttons'>
            <input type='submit' value='Create token'/>
        </div>
    </form>
</div>
