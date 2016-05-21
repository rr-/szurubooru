<div class='content-wrapper transparent' id='home'>
    <div class='messages'></div>
    <h1><%= ctx.name %></h1>
    <footer>Version: <a class='version' href='https://github.com/rr-/szurubooru/commits/master'><%= ctx.version %></a> (built <%= ctx.makeRelativeTime(ctx.buildDate) %>)</footer>
</div>
