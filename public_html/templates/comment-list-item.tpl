<div class="comment">
	<div class="avatar">
		<% if (comment.user.name && canViewUsers) { %>
			<a href="#/user/<%= comment.user.name %>">
		<% } %>

		<img width="40" height="40" class="author-avatar"
			src="/data/thumbnails/40x40/avatars/<%= comment.user.name || '!' %>"
			alt="<%= comment.user.name || 'Anonymous user' %>"/>

		<% if (comment.user.name && canViewUsers) { %>
			</a>
		<% } %>
	</div>

	<div class="body">
		<div class="header">
			<span class="nickname">
				<% if (comment.user.name && canViewUsers) { %>
					<a href="#/user/<%= comment.user.name %>">
				<% } %>

				<%= comment.user.name || 'Anonymous user' %>

				<% if (comment.user.name && canViewUsers) { %>
					</a>
				<% } %>
			</span>

			<span class="date" title="<%= util.formatAbsoluteTime(comment.creationTime) %>">
				<%= util.formatRelativeTime(comment.creationTime) %>
			</span>

			<span class="score">
				Score: <%= comment.score %>
			</span>

			<span class="ops"><!--
				--><% if (canVote) { %><!--
					--><a href="#" class="score-up <% print(comment.ownScore === 1 ? 'active' : '') %>"><!--
						-->vote up<!--
					--></a><!--
					--><a href="#" class="score-down <% print(comment.ownScore === -1 ? 'active' : '') %>"><!--
						-->vote down<!--
					--></a><!--
				--><% } %><!--

				--><% if (canEditComment) { %><!--
					--><a href="#" class="edit"><!--
						-->edit<!--
					--></a><!--
				--><% } %><!--

				--><% if (canDeleteComment) { %><!--
					--><a href="#" class="delete"><!--
						-->delete<!--
					--></a><!--
				--><% } %><!--
			--></span>
		</div>

		<div class="content">
			<%= util.formatMarkdown(comment.text) %>
		</div>
	</div>
</div>
