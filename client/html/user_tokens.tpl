<div id='user-tokens'>
    <div class='messages'></div>
    <% if (ctx.tokens.length > 0) { %>
    <div class="token-flex-container">
        <div class="token-flex-row">
            <div>Token</div>
            <div>Actions</div>
        </div>
        <% _.each(ctx.tokens, function(token, index) { %>
        <div class="token-flex-row">
            <div><%= token.token %></div>
            <div>
                <form id='token<%= index %>'>
                    <input type='hidden' name='token' value='<%= token.token %>'/>
                    <input type='submit' value='Delete token'/>
                </form>
            </div>
        </div>
        <% }); %>
    </div>
    <% } else { %>
        <h2>No Registered Tokens</h2>
    <% } %>
    <form id='create-token-form'>
        <div class='buttons'>
            <input type='submit' value='Create token'/>
        </div>
    </form>
</div>
