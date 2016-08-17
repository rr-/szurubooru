<%- ctx.postCount %> posts
<span class=sep>&bull;</span>
<%= ctx.makeFileSize(ctx.diskUsage) %>
<span class=sep>&bull;</span>
Build <a class='version' href='https://github.com/rr-/szurubooru/commits/master'><%- ctx.version %></a>
from <%= ctx.makeRelativeTime(ctx.buildDate) %>
<% if (ctx.canListSnapshots) { %>
    <span class=sep>&bull;</span>
    <a href='/history'>History</a>
<% } %>
