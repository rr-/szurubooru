<div class='post-container'></div>
<% if (ctx.featuredPost) { %>
    <aside>
        Featured&nbsp;post:&nbsp;<%= ctx.makePostLink(ctx.featuredPost.id, true) %>,<wbr>
        posted&nbsp;<%= ctx.makeRelativeTime(ctx.featuredPost.creationTime) %>&nbsp;by&nbsp;<%= ctx.makeUserLink(ctx.featuredPost.user) %>
    </aside>
<% } %>
