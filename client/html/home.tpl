<div class='content-wrapper transparent' id='home'>
    <div class='messages'></div>
    <h1><%= ctx.name %></h1>
    <footer>Version: <span class='version'><%= ctx.version %></span> (built <%= ctx.makeRelativeTime(ctx.buildDate) %>)</footer>
</div>
