<div id="post-view-wrapper">
	<div id="sidebar">
		<ul class="essential">
			<li>
				<a class="download" href="/data/posts/<%= post.name %>">
					<i class="fa fa-download"></i>
					<br/>
					<%= post.contentExtension + ', ' + formatFileSize(post.originalFileSize) %>
				</a>
			</li>
		</ul>

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

		<ul class="other-info">

			<li>
				Rating:
				<span class="safety-<%= post.safety %>">
					<%= post.safety %>
				</span>
			</li>

			<% if (post.originalFileSize) { %>
				<li>
					File size:
					<%= formatFileSize(post.originalFileSize) %>
				</li>
			<% } %>

			<% if (post.contentType == 'image') { %>
				<li>
					Image size:
					<%= post.imageWidth + 'x' + post.imageHeight %>
				</li>
			<% } %>

			<% if (post.lastEditTime !== post.uploadTime) { %>
				<li>
					Edited:
					<%= formatRelativeTime(post.lastEditTime) %>
				</li>
			<% } %>

			<% if (post.featureCount > 0) { %>
				<li>
					Featured: <%= post.featureCount %> <%= post.featureCount < 2 ? 'time' : 'times' %>
					<small>(<%= formatRelativeTime(post.lastFeatureTime) %>)</small>
				</li>
			<% } %>

			<% if (post.source) { %>
				<li>
					Source:&nbsp;<!--
					--><a href="<%= post.source %>"><!--
						--><%= post.source.trim() %>
					</a>
				</li>
			<% } %>

		</ul>

		<% if (_.any(privileges) || _.any(editPrivileges)) { %>
			<h1>Options</h1>

			<ul class="operations">
				<% if (_.any(editPrivileges)) { %>
					<li>
						<a href="#" class="edit">
							Edit
						</a>
					</li>
				<% } %>

				<% if (privileges.canDeletePosts) { %>
					<li>
						<a href="#" class="delete">
							Delete
						</a>
					</li>
				<% } %>

				<% if (privileges.canFeaturePosts) { %>
					<li>
						<a href="#" class="feature">
							Feature
						</a>
					</li>
				<% } %>
			</ul>
		<% } %>

	</div>

	<div id="post-view">
		<div class="messages"></div>

		<div class="post-edit-wrapper">
			<%= postEditTemplate({post: post, privileges: editPrivileges}) %>
		</div>

		<%= postContentTemplate({post: post}) %>
	</div>
</div>
