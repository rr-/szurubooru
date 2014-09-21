<div id="post-view-wrapper">
	<div id="sidebar">
		<h1>Tags (<%= _.size(post.tags) %>)</h1>
		<ul class="tags">
			<% _.each(post.tags, function(tag) { %>
				<li>
					<a href="#/posts/search=<%= tag.name %>">
						<%= tag.name %>
						<span class="usages"><%= (tag.usages) %></span>
					</a>
				</li>
			<% }) %>
		</ul>

		<h1>Details</h1>

		<div class="author-box">
			<% if (post.user.name) { %>
				<a href="#/user/<%= post.user.name %>">
			<% } %>

			<img class="author-avatar"
				src="/data/thumbnails/40x40/avatars/<%= post.user.name || '!' %>"
				alt="<%= post.user.name || 'Anonymous user' %>"/>

			<span class="author-name">
				<%= post.user.name || 'Anonymous user' %>
			</span>

			<% if (post.user.name) { %>
				</a>
			<% } %>

			<br/>

			<span class="date"><%= formatRelativeTime(post.uploadTime) %></span>
		</div>
	</div>

	<div id="post-view">
		<%= postContentTemplate({post: post}) %>
	</div>
</div>
