<% function showUser(name) { %>
    <% var showLink = typeof(canViewUsers) !== 'undefined' && canViewUsers && name %>

    <% if (showLink) { %>
        <a href="#/user/<%= name %>">
    <% } %>

    <img width="25" height="25" class="author-avatar"
        src="/data/thumbnails/25x25/avatars/<%= name || '!' %>"
        alt="<%= name || 'Anonymous user' %>"/>

    <%= name || 'Anonymous user' %>

    <% if (showLink) { %>
        </a>
    <% } %>
<% } %>

<div id="home">
    <h1><%= title %></h1>
    <p class="subheader">
        Serving <%= globals.postCount || 0 %> posts (<%= util.formatFileSize(globals.postSize || 0) %>)
    </p>

    <% if (post && post.id) { %>
        <div class="post" style="width: <%= post.imageWidth || 800 %>px">
            <div id="post-content-target">
            </div>

            <div class="post-footer">

                <span class="left">
                    <% var showLink = canViewPosts %>

                    <% if (showLink) { %>
                        <a href="#/post/<%= post.id %>">
                    <% } %>

                    <%= post.idMarkdown %>

                    <% if (showLink) { %>
                        </a>
                    <% } %>

                    uploaded
                    <%= util.formatRelativeTime(post.uploadTime) %>
                    by
                    <% showUser(post.user.name) %>
                </span>

                <span class="right">
                    featured
                    <%= util.formatRelativeTime(post.lastFeatureTime) %>
                    by
                    <% showUser(user.name) %>
                </span>

            </div>
        </div>
    <% } %>

    <p>
        <small class="version">
            Version: <a href="//github.com/rr-/szurubooru/commits/master"><%= version %></a> (built <%= util.formatRelativeTime(buildTime) %>)
            |
            <a href="#/history">Recent tag and post edits</a>
        </small>
    </p>
</div>
