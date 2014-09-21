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
	</div>

	<div id="post-view">
		<%= postContentTemplate({post: post}) %>
	</div>
</div>
