<div class="comment">
	<div class="avatar">
		<% if (comment.user.name) { %>
			<a href="#/user/<%= comment.user.name %>">
		<% } %>

		<img class="author-avatar"
			src="/data/thumbnails/40x40/avatars/<%= comment.user.name || '!' %>"
			alt="<%= comment.user.name || 'Anonymous user' %>"/>

		<% if (comment.user.name) { %>
			</a>
		<% } %>
	</div>

	<div class="body">
		<div class="header">
			<span class="nickname">
				<% if (comment.user.name) { %>
					<a href="#/user/<%= comment.user.name %>">
				<% } %>

				<%= comment.user.name || 'Anonymous user' %>

				<% if (comment.user.name) { %>
					</a>
				<% } %>
			</span>

			<span class="date" title="<%= comment.creationTime %>">
				<%= formatRelativeTime(comment.creationTime) %>
			</span>

			<span class="ops">
				<% if (canEditComment) { %>
					<a class="edit" href="#"><!--
						-->edit<!--
					--></a>
				<% } %>

				<% if (canDeleteComment) { %>
					<a class="delete" href="#"><!--
						-->delete<!--
					--></a>
				<% } %>
			</span>
		</div>

		<div class="content">
			<%= formatMarkdown(comment.text) %>
		</div>
	</div>
</div>
