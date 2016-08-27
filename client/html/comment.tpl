<div class='comment'>
    <div class='avatar'>
        <% if (ctx.comment.user && ctx.comment.user.name && ctx.canViewUsers) { %>
            <a href='/user/<%- encodeURIComponent(ctx.comment.user.name) %>'>
        <% } %>

        <%= ctx.makeThumbnail(ctx.comment.user ? ctx.comment.user.avatarUrl : null) %>

        <% if (ctx.comment.user && ctx.comment.user.name && ctx.canViewUsers) { %>
            </a>
        <% } %>
    </div>

    <div class='body'>
        <header><%
            %><span class='nickname'><%
                %><% if (ctx.comment.user && ctx.comment.user.name && ctx.canViewUsers) { %><%
                    %><a href='/user/<%- encodeURIComponent(ctx.comment.user.name) %>'><%
                %><% } %><%

                %><%- ctx.comment.user ? ctx.comment.user.name : 'Deleted user' %><%

                %><% if (ctx.comment.user && ctx.comment.user.name && ctx.canViewUsers) { %><%
                    %></a><%
                %><% } %><%
            %></span><%

            %><wbr><%

            %><span class='date'><%
                %><%= ctx.makeRelativeTime(ctx.comment.creationTime) %><%
            %></span><%

            %><wbr><%

            %><span class='score-container'></span><%

            %><wbr><%

            %><% if (ctx.canEditComment) { %><%
                %><a href class='edit'><%
                    %><i class='fa fa-pencil'></i> edit<%
                %></a><%
            %><% } %><%

            %><wbr><%

            %><% if (ctx.canDeleteComment) { %><%
                %><a href class='delete'><%
                    %><i class='fa fa-remove'></i> delete<%
                %></a><%
            %><% } %><%
        %></header>

        <div class='comment-form-container'></div>
    </div>
</div>
