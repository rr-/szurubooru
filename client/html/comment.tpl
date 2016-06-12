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

        <div class='comment-form-container'></div>
    </div>
</div>
