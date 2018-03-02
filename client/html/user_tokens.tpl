<div id='user-tokens'>
    <div class='messages'></div>
    <% if (ctx.tokens.length > 0) { %>
    <div class="token-flex-container">
        <% _.each(ctx.tokens, function(token, index) { %>
        <div class="token-flex-row floor">
            <div class="token-flex-column token-info">
                <div class="token-flex-row">
                    <div>Token:</div>
                    <div><%= token.token %></div>
                </div>
                <div class="token-flex-row">
                    <div>Note:</div>
                    <div><%= token.note %></div>
                </div>
                <div class="token-flex-row">
                    <div>Created:</div>
                    <div><%= new Date(token.creationTime).toLocaleDateString() %></div>
                </div>
                <% if (token.expirationTime) { %>
                <div class="token-flex-row">
                    <div>Expires:</div>
                    <div><%= new Date(token.expirationTime).toLocaleDateString() %></div>
                </div>
                <% } %>
            </div>
            <div class="token-flex-column token-actions">
                <div>
                    <form class='token' data-token-id='<%= index %>'>
                        <input type='hidden' name='token' value='<%= token.token %>'/>
                        <input type='submit' value='Delete token'/>
                    </form>
                </div>
            </div>
        </div>
        <% }); %>
    </div>
    <% } else { %>
        <h2>No Registered Tokens</h2>
    <% } %>
    <div class='flex-centered'>
        <form id='create-token-form'>
            <div class="token-flex-container">
                <div class="token-flex-row">
                    <div>Note:</div>
                    <div><input name='note', type='textbox'/></div>
                </div>
                <div class="token-flex-row">
                    <div>Expiration:</div>
                    <div><input name='expirationTime' type='date'/></div>
                </div>
                <div class="token-flex-row" style='justify-content: end;'>
                    <div class='buttons'>
                        <input type='submit' value='Create token'/>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
