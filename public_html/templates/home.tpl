<div id="home">
	<h1><%= title %></h1>
	<p class="subheader">
		Serving <%= globals.postCount || 0 %> posts (<%= formatFileSize(globals.postSize || 0) %>)
	</p>

	<% if (post && post.id) { %>
		<div class="post">
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
					<%= formatRelativeTime(post.uploadTime) %>
				</span>

				<span class="right">
					featured
					<%= formatRelativeTime(post.lastFeatureTime) %>
					by

					<% var showLink = canViewUsers && user.name %>

					<% if (showLink) { %>
						<a href="#/user/<%= user.name %>">
					<% } %>

					<img width="25" height="25" class="author-avatar"
						src="/data/thumbnails/25x25/avatars/<%= user.name || '!' %>"
						alt="<%= user.name || 'Anonymous user' %>"/>

					<%= user.name || 'Anonymous user' %>

					<% if (showLink) { %>
						</a>
					<% } %>
				</span>

			</div>
		</div>
	<% } %>

	<p>
		<small class="version">
			Version: <a href="//github.com/rr-/szurubooru/commits/master"><%= version %></a> (built <%= formatRelativeTime(buildTime) %>)
			|
			<a href="#/history">Recent tag and post edits</a>
		</small>
	</p>
</div>
