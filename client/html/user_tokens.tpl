<div id='user-tokens'>
    <div class='messages'></div>
    <% if (ctx.tokens.length > 0) { %>
    <div class="token-flex-container">
        <% _.each(ctx.tokens, function(token, index) { %>
        <div class="token-flex-row">
            <div class="token-flex-column token-flex-labels">
                <div class="token-flex-row">Token:</div>
                <div class="token-flex-row">Note:</div>
                <div class="token-flex-row">Created:</div>
                <div class="token-flex-row">Expires:</div>
            </div>
            <div class="token-flex-column full-width">
                <div class="token-flex-row"><%= token.token %></div>
                <div class="token-flex-row"><%= token.note %></div>
                <div class="token-flex-row"><%= ctx.makeRelativeTime(token.creationTime) %></div>
                <% if (token.expirationTime) { %>
                <div class="token-flex-row"><%= ctx.makeRelativeTime(token.expirationTime) %></div>
                <% } else { %>
                    <div class="token-flex-row">No expiration</div>
                <% } %>
            </div>
        </div>
        <div class="token-flex-row">
            <div class="token-flex-column full-width">
                <div class="token-flex-row">
                    <form class='token' data-token-id='<%= index %>'>
                        <input type='hidden' name='token' value='<%= token.token %>'/>
                        <% if (token.isCurrentAuthToken) { %>
                            <input type='submit' value='Delete and logout'/>
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
        <ul class="input">
            <li>
                <label>Note</label>
                <input name='note', type='textbox'/>
            </li>
            <li>
                <label>Expires</label>
                <input name='expirationTime' type='date'/>
            </li>
        </ul>
        <div class='buttons'>
            <input type='submit' value='Create token'/>
        </div>
    </form>
</div>
