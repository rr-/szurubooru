<div class='content-wrapper transparent' id='home'>
    <div class='messages'></div>
    <header>
        <h1><%= ctx.name %></h1>
        <p>Serving <%= ctx.postCount %> posts (<%= ctx.makeFileSize(ctx.diskUsage) %>)</p>
    </header>
    <footer>Version: <a class='version' href='https://github.com/rr-/szurubooru/commits/master'><%= ctx.version %></a> (built <%= ctx.makeRelativeTime(ctx.buildDate) %>)</footer>
</div>
