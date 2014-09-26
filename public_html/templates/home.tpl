<div id="home">
	<h1><%= title %></h1>
	<p>
		<small>Serving <%= globals.postCount %> posts (<%= formatFileSize(globals.postSize) %>)</small>
	</p>

	<% if (post) { %>
		<div class="post">
			<%= postContentTemplate({post: post}) %>
			<div class="post-footer">

				<span class="left">
					<a href="#/post/<%= post.id %>">
						<%= post.idMarkdown %>
					</a>

					uploaded
					<%= formatRelativeTime(post.uploadTime) %>
				</span>

				<span class="right">
					featured by

					<% if (post.user.name) { %>
						<a href="#/user/<%= post.user.name %>">
					<% } %>

					<img class="author-avatar"
						src="/data/thumbnails/25x25/avatars/<%= post.user.name || '!' %>"
						alt="<%= post.user.name || 'Anonymous user' %>"/>

					<%= post.user.name || 'Anonymous user' %>

					<% if (post.user.name) { %>
						</a>
					<% } %>
				</span>

			</div>
		</div>
	<% } %>
</div>
