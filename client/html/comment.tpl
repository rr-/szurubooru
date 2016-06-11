<div class='comment'>
    <div class='avatar'>
        <% if (ctx.comment.user.name && ctx.canViewUsers) { %>
            <a href='/user/<%= ctx.comment.user.name %>'>
        <% } %>

        <%= ctx.makeThumbnail(ctx.comment.user.avatarUrl) %>

        <% if (ctx.comment.user.name && ctx.canViewUsers) { %>
            </a>
        <% } %>
    </div>

    <div class='body'>
        <header><!--
            --><span class='nickname'><!--
                --><% if (ctx.comment.user.name && ctx.canViewUsers) { %><!--
                    --><a href='/user/<%= ctx.comment.user.name %>'><!--
                --><% } %><!--

                --><%= ctx.comment.user.name %><!--

                --><% if (ctx.comment.user.name && ctx.canViewUsers) { %><!--
                    --></a><!--
                --><% } %><!--
            --></span><!--

            --><span class='date'><!--
                --><%= ctx.makeRelativeTime(ctx.comment.creationTime) %><!--
            --></span><!--

            --><span class='score-container'></span><!--

            --><% if (ctx.canEditComment) { %><!--
                --><a class='edit' href='#'><!--
                    --><i class='fa fa-pencil'></i> edit<!--
                --></a><!--
            --><% } %><!--

            --><% if (ctx.canDeleteComment) { %><!--
                --><a class='delete' href='#'><!--
                    --><i class='fa fa-remove'></i> delete<!--
                --></a><!--
            --><% } %><!--
        --></header>

        <div class='tabs'>
            <form>
                <div class='tabs-wrapper'>
                    <div class='preview tab'>
                        <div class='content-wrapper'><div class='content'><%= ctx.makeMarkdown(ctx.comment.text) %></div></div>
                    </div>

                    <div class='edit tab'>
                        <textarea required minlength=1><%= ctx.comment.text %></textarea>
                    </div>
                </div>

                <nav class='buttons'>
                    <ul>
                        <li class='preview'><a href='#'>Preview</a></li>
                        <li class='edit'><a href='#'>Edit</a></li>
                    </ul>
                </nav>

                <nav class='actions'>
                    <input type='submit' class='save' value='Save'/>
                    <input type='button' class='cancel discourage' value='Cancel'/>
                </nav>
            </form>

            <div class='messages'></div>
        </div>
    </div>
</div>
