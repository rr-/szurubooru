<div class='post-list-header'><%
    %><form class='horizontal'><%
        %><%= ctx.makeTextInput({text: 'Search query', id: 'search-text', name: 'search-text', value: ctx.parameters.query}) %><%
        %><wbr/><%
        %><input class='mousetrap' type='submit' value='Search'/><%
        %><wbr/><%
        %><input data-safety=safe type='button' class='mousetrap safety safety-safe <%- ctx.settings.listPosts.safe ? '' : 'disabled' %>'/><%
        %><input data-safety=sketchy type='button' class='mousetrap safety safety-sketchy <%- ctx.settings.listPosts.sketchy ? '' : 'disabled' %>'/><%
        %><input data-safety=unsafe type='button' class='mousetrap safety safety-unsafe <%- ctx.settings.listPosts.unsafe ? '' : 'disabled' %>'/><%
        %><wbr/><%
        %><a class='mousetrap button append' href='/help/search/posts'>Syntax help</a><%
        %><% if (ctx.canMassTag) { %><%
            %><wbr/><%
            %><span class='masstag'><%
                %><span class='append masstag-hint'>Tagging with:</span><%
                %><a href class='mousetrap button append open-masstag'>Mass tag</a><%
                %><wbr/><%
                %><%= ctx.makeTextInput({name: 'masstag', value: ctx.parameters.tag}) %><%
                %><input class='mousetrap start-tagging' type='submit' value='Start tagging'/><%
                %><a href class='mousetrap button append stop-tagging'>Stop tagging</a><%
            %></span><%
        %><% } %><%
    %></form><%
%></div>
